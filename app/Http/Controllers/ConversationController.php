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

        // Add user message
        $userMessage = $conversation->messages()->create([
            'content' => $request->message,
            'role' => 'user',
            'model_name' => $request->model,
        ]);

        // Get AI response
        try {
            $messages = [
                ['role' => 'user', 'content' => $request->message]
            ];

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

            // Generate title based on first message
            $conversation->generateTitle();

            return redirect()->route('chat.show', $conversation);

        } catch (\Exception $e) {
            // If AI call fails, delete the conversation
            $conversation->delete();

            return redirect()->back()->with('error', 'Failed to get AI response: ' . $e->getMessage());
        }
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

        // Add user message
        $conversation->messages()->create([
            'content' => $request->message,
            'role' => 'user',
            'model_name' => $request->model,
        ]);

        try {
            // Get conversation history for context
            $messages = $conversation->messages()
                ->where('role', '!=', 'system')
                ->get()
                ->map(fn($msg) => [
                    'role' => $msg->role,
                    'content' => $msg->content
                ])
                ->toArray();

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
}
