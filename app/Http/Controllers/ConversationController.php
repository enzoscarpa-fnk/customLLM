<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConversationController extends Controller
{
    public function __construct(
        private ChatService $chatService
    ) {}

    /**
     * Display conversations list with optional active conversation
     */
    public function index(?Conversation $conversation = null)
    {
        $user = auth()->user();

        $conversations = $user->conversations()
            ->with('latestMessage')
            ->get();

        $models = $this->chatService->getModels();

        $activeConversation = null;
        $messages = [];

        if ($conversation && $conversation->user_id === $user->id) {
            $activeConversation = $conversation;
            $messages = $conversation->messages()->get();
        }

        // Get user instructions with detailed logging
        $userInstructions = $user->getInstructionsOrDefault();

        // Debug logging
        \Log::info('ConversationController index - User ID:', ['user_id' => $user->id]);
        \Log::info('ConversationController index - Raw instructions from DB:', ['instructions' => $user->instructions]);
        \Log::info('ConversationController index - Processed userInstructions:', ['userInstructions' => $userInstructions]);

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'messages' => $messages,
            'models' => $models,
            'userPreferredModel' => $user->preferred_model,
            'userInstructions' => $userInstructions,
        ]);
    }

    /**
     * Create a new conversation with first message
     */
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:10000',
            'model' => 'required|string',
        ]);

        $user = auth()->user();

        // Update user's preferred model
        $user->updatePreferredModel($request->model);

        // Create new conversation
        $conversation = $user->conversations()->create([
            'title' => 'New chat',
            'model_name' => $request->model,
            'last_message_at' => now(),
        ]);

        // Process message for custom commands and instructions
        $processedMessage = $this->processUserMessage($request->message, $user);

        // Add user message (store original message, not processed)
        $userMessage = $conversation->messages()->create([
            'content' => $request->message,
            'role' => 'user',
            'model_name' => $request->model,
        ]);

        // Get AI response
        try {
            $messages = $this->buildMessagesWithInstructions($user, [
                ['role' => 'user', 'content' => $processedMessage]
            ]);

            $aiResponse = $this->chatService->sendMessage(
                messages: $messages,
                model: $request->model
            );

            // Add AI response
            $conversation->messages()->create([
                'content' => $aiResponse,
                'role' => 'assistant',
                'model_name' => $request->model,
            ]);

            // Update conversation timestamp
            $conversation->updateLastMessageTime();

            // Generate title based on assistant's first message
            $conversation->generateTitleWithAI();

            return redirect()->route('chat.show', $conversation);

        } catch (\Exception $e) {
            // If AI call fails, delete the conversation
            $conversation->delete();

            return redirect()->back()->with('error', 'Failed to get AI response: ' . $e->getMessage());
        }
    }

    /**
     * Delete existing conversation
     */
    public function destroy(Conversation $conversation)
    {
        $user = auth()->user();

        // Check ownership
        if ($conversation->user_id !== $user->id) {
            abort(403);
        }

        $conversation->delete();

        return redirect()->route('chat.index')->with('message', 'Conversation deleted successfully');
    }

    /**
     * Add message to existing conversation
     */
    public function addMessage(Request $request, Conversation $conversation)
    {
        $request->validate([
            'message' => 'required|string|max:10000',
            'model' => 'required|string',
        ]);

        $user = auth()->user();

        // Check ownership
        if ($conversation->user_id !== $user->id) {
            abort(403);
        }

        // Update user's preferred model
        $user->updatePreferredModel($request->model);

        // Update conversation model if changed
        if ($conversation->model_name !== $request->model) {
            $conversation->update(['model_name' => $request->model]);
        }

        // Process message for custom commands
        $processedMessage = $this->processUserMessage($request->message, $user);

        // Add user message (store original message, not processed)
        $conversation->messages()->create([
            'content' => $request->message,
            'role' => 'user',
            'model_name' => $request->model,
        ]);

        try {
            // Get conversation history for context
            $conversationMessages = $conversation->messages()
                ->where('role', '!=', 'system')
                ->get()
                ->map(function($msg) use ($request, $processedMessage) {
                    return [
                        'role' => $msg->role,
                        'content' => $msg->role === 'user' && $msg->content === $request->message
                            ? $processedMessage  // Use processed message for the current user message
                            : $msg->content
                    ];
                })
                ->toArray();

            $messages = $this->buildMessagesWithInstructions($user, $conversationMessages);

            $aiResponse = $this->chatService->sendMessage(
                messages: $messages,
                model: $request->model
            );

            // Add AI response
            $conversation->messages()->create([
                'content' => $aiResponse,
                'role' => 'assistant',
                'model_name' => $request->model,
            ]);

            // Update conversation timestamp
            $conversation->updateLastMessageTime();

            return redirect()->route('chat.show', $conversation);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to get AI response: ' . $e->getMessage());
        }
    }

    /**
     * Show specific conversation
     */
    public function show(Conversation $conversation)
    {
        // Check ownership
        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        return $this->index($conversation);
    }

    /**
     * Process user message for custom commands
     */
    private function processUserMessage(string $message, $user): string
    {
        $userInstructions = $user->instructions;

        if (!$userInstructions || !$userInstructions->enabled) {
            return $message;
        }

        $customCommands = $userInstructions->custom_commands ?? [];

        // Log for debugging
        \Log::info('Processing user message for commands:', [
            'original_message' => $message,
            'available_commands' => array_column($customCommands, 'name')
        ]);

        foreach ($customCommands as $command) {
            $commandName = $command['name'];
            $commandResponse = $command['response'];

            // Check if message starts with the command
            if (str_starts_with(trim($message), $commandName)) {
                // Extract any parameters after the command
                $commandParams = trim(substr($message, strlen($commandName)));

                // Build the processed message with the command response
                $processedMessage = $commandResponse;

                // If there are parameters, add them as additional context
                if (!empty($commandParams)) {
                    $processedMessage .= "\n\nAdditional context: " . $commandParams;
                }

                \Log::info('Command matched and processed:', [
                    'command' => $commandName,
                    'original_message' => $message,
                    'processed_message' => $processedMessage
                ]);

                return $processedMessage;
            }
        }

        // No command matched, return original message
        return $message;
    }

    /**
     * Build messages array with user instructions
     */
    private function buildMessagesWithInstructions($user, array $messages): array
    {
        $userInstructions = $user->instructions;

        if (!$userInstructions || !$userInstructions->enabled) {
            return $messages;
        }

        $systemMessages = [];
        $systemContent = [];

        if (!empty($userInstructions->about_you)) {
            $systemContent[] = "About the user: " . $userInstructions->about_you;
        }

        if (!empty($userInstructions->behavior)) {
            $systemContent[] = "Behavior instructions: " . $userInstructions->behavior;
        }

        if (!empty($systemContent)) {
            $systemMessages[] = [
                'role' => 'system',
                'content' => implode("\n\n", $systemContent)
            ];
        }

        return array_merge($systemMessages, $messages);
    }
}
