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

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'messages' => $messages,
            'models' => $models,
            'userPreferredModel' => $user->preferred_model,
            'userInstructions' => $user->getInstructionsOrDefault(),
        ]);
    }

    /**
     * Store or update user instructions
     */
    public function storeInstructions(Request $request)
    {
        $request->validate([
            'about_you' => 'nullable|string|max:2000',
            'behavior' => 'nullable|string|max:2000',
            'custom_commands' => 'nullable|array',
            'custom_commands.*.name' => 'required|string|max:50',
            'custom_commands.*.description' => 'required|string|max:200',
            'custom_commands.*.response' => 'required|string|max:1000',
            'enabled' => 'boolean'
        ]);

        $user = auth()->user();

        $instructions = [
            'about_you' => $request->about_you,
            'behavior' => $request->behavior,
            'custom_commands' => $request->custom_commands ?? [],
            'enabled' => $request->enabled ?? true
        ];

        $user->updateInstructions($instructions);

        return redirect()->back()->with('message', 'Instructions updated successfully');
    }

    /**
     * Update specific instruction type
     */
    public function updateInstruction(Request $request)
    {
        $request->validate([
            'type' => 'required|in:about_you,behavior,custom_commands',
            'data' => 'required'
        ]);

        $user = auth()->user();
        $currentInstructions = $user->getInstructionsOrDefault();

        $currentInstructions[$request->type] = $request->data;

        $user->updateInstructions($currentInstructions);

        return response()->json([
            'success' => true,
            'message' => 'Instruction updated successfully',
            'instructions' => $user->getInstructionsOrDefault()
        ]);
    }

    /**
     * Delete specific instruction type
     */
    public function deleteInstruction(Request $request)
    {
        $request->validate([
            'type' => 'required|in:about_you,behavior,custom_commands'
        ]);

        $user = auth()->user();
        $currentInstructions = $user->getInstructionsOrDefault();

        if ($request->type === 'custom_commands') {
            $currentInstructions[$request->type] = [];
        } else {
            $currentInstructions[$request->type] = '';
        }

        $user->updateInstructions($currentInstructions);

        return response()->json([
            'success' => true,
            'message' => 'Instruction deleted successfully',
            'instructions' => $user->getInstructionsOrDefault()
        ]);
    }

    /**
     * Delete specific custom command
     */
    public function deleteCustomCommand(Request $request)
    {
        $request->validate([
            'command_name' => 'required|string'
        ]);

        $user = auth()->user();
        $currentInstructions = $user->getInstructionsOrDefault();

        $commands = $currentInstructions['custom_commands'] ?? [];
        $commands = array_filter($commands, function($command) use ($request) {
            return $command['name'] !== $request->command_name;
        });

        $currentInstructions['custom_commands'] = array_values($commands);
        $user->updateInstructions($currentInstructions);

        return response()->json([
            'success' => true,
            'message' => 'Command deleted successfully',
            'instructions' => $user->getInstructionsOrDefault()
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

        // Add user message
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

        // Add user message
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
                ->map(fn($msg) => [
                    'role' => $msg->role,
                    'content' => $msg->role === 'user' && $msg->content === $request->message
                        ? $processedMessage
                        : $msg->content
                ])
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
        $userInstructions = $user->getInstructionsOrDefault();

        if (!$userInstructions || !$userInstructions['enabled']) {
            return $message;
        }

        $customCommands = $userInstructions['custom_commands'] ?? [];

        foreach ($customCommands as $command) {
            if (str_starts_with(trim($message), $command['name'])) {
                $commandParams = trim(substr($message, strlen($command['name'])));
                return $command['response'] . ($commandParams ? "\n\nUser request: " . $commandParams : "");
            }
        }

        return $message;
    }

    /**
     * Build messages array with user instructions
     */
    private function buildMessagesWithInstructions($user, array $messages): array
    {
        $userInstructions = $user->getInstructionsOrDefault();

        if (!$userInstructions || !$userInstructions['enabled']) {
            return $messages;
        }

        $systemMessages = [];
        $systemContent = [];

        if (!empty($userInstructions['about_you'])) {
            $systemContent[] = "About the user: " . $userInstructions['about_you'];
        }

        if (!empty($userInstructions['behavior'])) {
            $systemContent[] = "Behavior instructions: " . $userInstructions['behavior'];
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
