<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'model_name',
        'last_message_at'
    ];

    protected $casts = [
        'last_message_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function updateLastMessageTime(): void
    {
        $this->update(['last_message_at' => now()]);
    }

    public function generateTitleWithAI(): void
    {
        $firstAssistantMessage = $this->messages()->where('role', 'assistant')->first();
        if ($firstAssistantMessage && $this->title === 'New chat') {
            try {
                $chatService = app(\App\Services\ChatService::class);

                $titlePrompt = [
                    [
                        'role' => 'user',
                        'content' => "Generate a short, descriptive title (maximum 6 words) for a conversation based on this AI response: \n\n" . $firstAssistantMessage->content
                    ]
                ];

                $generatedTitle = $chatService->sendMessage(
                    messages: $titlePrompt,
                    model: $this->model_name
                );

                // Clean and limit the generated title
                $title = trim(str_replace(['"', "'", "\n", "\r"], '', $generatedTitle));
                $title = \Str::limit($title, 60);

                $this->update(['title' => $title]);

            } catch (\Exception $e) {
                // Fallback to simple title if AI generation fails
                $content = strip_tags($firstAssistantMessage->content);
                $title = \Str::limit($content, 50);
                $this->update(['title' => $title]);
            }
        }
    }
}
