<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ChatService
{
    private $baseUrl;
    private $apiKey;
    private $client;
    public const DEFAULT_MODEL = 'openai/gpt-4.1-mini';

    public function __construct()
    {
        $this->baseUrl = config('services.openrouter.base_url', 'https://openrouter.ai/api/v1');
        $this->apiKey = config('services.openrouter.api_key');
        $this->client = $this->createOpenAIClient();
    }

    private function createOpenAIClient(): \OpenAI\Client
    {
        return \OpenAI::factory()
            ->withApiKey($this->apiKey)
            ->withBaseUri($this->baseUrl)
            ->make()
            ;
    }

    /**
     * @return array<array-key, array{
     *     id: string,
     *     name: string,
     *     context_length: int,
     *     max_completion_tokens: int,
     *     pricing: array{prompt: int, completion: int}
     * }>
     */
    public function getModels(): array
    {
        return cache()->remember('openai.models', now()->addHour(), function () {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/models');

            return collect($response->json()['data'])
                ->sortBy('name')
                ->map(function ($model) {
                    return [
                        'id' => $model['id'],
                        'name' => $model['name'],
                        'context_length' => $model['context_length'],
                        'max_completion_tokens' => $model['top_provider']['max_completion_tokens'],
                        'pricing' => $model['pricing'],
                    ];
                })
                ->values()
                ->all()
                ;
        });
    }

    /**
     * @return array{role: 'system', content: string}
     */
    private function getChatSystemPrompt(): array
    {
        $user = auth()->user();
        $now = now()->locale('fr')->format('l d F Y H:i');

        $basePrompt = "You're a chat assistant. The actual date and time is {$now}. You're used by {$user->name}.";

        $userInstructions = $user->instructions;
        if ($userInstructions && $userInstructions->enabled) {
            $customInstructions = $userInstructions->formatted_instructions;
            if ($customInstructions) {
                $basePrompt .= "\n\nInstructions personnalisées:\n" . $customInstructions;
            }
        }

        return [
            'role' => 'system',
            'content' => $basePrompt,
        ];
    }

    /**
     * @param array{role: 'user'|'assistant'|'system'|'function', content: string} $messages
     * @param string|null $model
     * @param float $temperature
     *
     * @return string
     */
    public function sendMessage(array $messages, string $model = null, float $temperature = 0.7): string
    {
        try {
            logger()->info('Sending message', [
                'model' => $model,
                'temperature' => $temperature,
            ]);

            $models = collect($this->getModels());
            if (!$model || !$models->contains('id', $model)) {
                $model = self::DEFAULT_MODEL;
                logger()->info('Default model used:', ['model' => $model]);
            }

            $processedMessages = $this->processCustomCommands($messages);

            $finalMessages = [$this->getChatSystemPrompt(), ...$processedMessages];

            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => $finalMessages,
                'temperature' => $temperature,
            ]);

            logger()->info('Response received:', ['response' => $response]);

            return $response->choices[0]->message->content;

        } catch (\Exception $e) {
            logger()->error('Error in sendMessage:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function processCustomCommands(array $messages): array
    {
        $user = auth()->user();
        $userInstructions = $user->instructions;

        if (!$userInstructions || !$userInstructions->enabled || !$userInstructions->custom_commands) {
            return $messages;
        }

        $commands = collect($userInstructions->custom_commands);

        return collect($messages)->map(function ($message) use ($commands) {
            if ($message['role'] === 'user') {
                $content = $message['content'];

                // Chercher des commandes dans le message
                foreach ($commands as $command) {
                    if (str_starts_with(trim($content), $command['name'])) {
                        // Remplacer la commande par sa réponse
                        $commandText = $command['name'];
                        $parameters = trim(str_replace($commandText, '', $content));

                        $newContent = $command['response'];
                        if ($parameters) {
                            $newContent .= " Parameters: " . $parameters;
                        }

                        $message['content'] = $newContent;
                        break;
                    }
                }
            }

            return $message;
        })->toArray();
    }

    public function stream(array $messages, string $model = null, float $temperature = 0.7): \OpenAI\Responses\StreamResponse
    {
        try {
            logger()->info('Sending streamed message', [
                'model' => $model,
                'temperature' => $temperature,
            ]);

            $models = collect($this->getModels());
            if (!$model || !$models->contains('id', $model)) {
                $model = self::DEFAULT_MODEL;
                logger()->info('Default model used:', ['model' => $model]);
            }

            $processedMessages = $this->processCustomCommands($messages);

            $finalMessages = [$this->getChatSystemPrompt(), ...$processedMessages];

            $stream = $this->client->chat()->createStreamed([
                'model' => $model,
                'messages' => $finalMessages,
                'temperature' => $temperature,
                'stream' => true,
            ]);

            return $stream;
        } catch (\Exception $e) {
            logger()->error('Stream error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
