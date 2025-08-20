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
                $basePrompt .= "\n\nCustom Instructions:\n" . $customInstructions;
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
                'tools' => [
                    ['type' => 'function', 'function' => $this->getWeatherFunctionDefinition()[0]]
                ],
                'tool_choice' => 'auto',
            ]);

            $message = $response->choices[0]->message;

            // Check if model will use a function
            if (property_exists($message, 'tool_calls') && $message->tool_calls !== null && !empty($message->tool_calls)) {
                $toolCall = $message->tool_calls[0];
                $functionName = $toolCall->function->name;
                $arguments = json_decode($toolCall->function->arguments, true);

                logger()->info('Function call detected', [
                    'function' => $functionName,
                    'arguments' => $arguments
                ]);

                if ($functionName === 'get_weather') {
                    $city = $arguments['city'] ?? null;
                    $country = $arguments['country'] ?? null;

                    if ($city) {
                        $location = $city . ($country ? ", $country" : "");

                        $originalMessage = end($messages)['content'] ?? '';
                        $isFrench = $this->isFrench($originalMessage);

                        $weatherData = $this->getWeatherData($location, $isFrench);

                        $followUpMessages = array_merge(
                            $finalMessages,
                            [
                                ['role' => 'assistant', 'content' => null, 'tool_calls' => [$toolCall]],
                                ['role' => 'tool', 'tool_call_id' => $toolCall->id, 'content' => $weatherData]
                            ]
                        );

                        $followUpResponse = $this->client->chat()->create([
                            'model' => $model,
                            'messages' => $followUpMessages,
                            'temperature' => $temperature
                        ]);

                        return $followUpResponse->choices[0]->message->content;
                    }
                }
            }

            logger()->info('Response received:', ['response' => $response]);

            return $message->content;

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

        return collect($messages)->map(function ($message) use ($user) {
            if ($message['role'] === 'user') {
                $content = $message['content'];

                $userInstructions = $user->instructions;
                if ($userInstructions && $userInstructions->enabled && $userInstructions->custom_commands) {
                    $commands = collect($userInstructions->custom_commands);

                    foreach ($commands as $command) {
                        if (str_starts_with(trim($content), $command['name'])) {
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
            }

            return $message;
        })->toArray();
    }

    public function stream(array $messages, ?string $model = null, float $temperature = 0.7): \OpenAI\Responses\StreamResponse
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

    private function getWeatherFunctionDefinition(): array
    {
        return [
            [
                'name' => 'get_weather',
                'description' => "Get current weather and forecast for any city. Trigger for weather-related queries in any language: météo, temps, weather, clima, tiempo, wetter, tempo, погода, 天気, temperature, rain, sun, clouds, wind, etc.",
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'city' => [
                            'type' => 'string',
                            'description' => 'City name in any language, e.g., Paris, Londres, Madrid, 東京, Москва'
                        ],
                        'country' => [
                            'type' => 'string',
                            'description' => 'Optional country code or name, e.g., FR, BE, US, France, Belgique'
                        ]
                    ],
                    'required' => ['city']
                ]
            ]
        ];
    }


    private function getWeatherData(string $location, bool $isFrench = true): string
    {
        // Try variants of the location
        $locationVariants = [
            $location,
            str_replace(' ', '-', $location),
            str_replace('-', ' ', $location),
            trim($location),
        ];

        foreach ($locationVariants as $variant) {
            try {
                logger()->info("Trying weather API with location variant: $variant");

                // Current weather
                $currentUrl = config('services.openweather.base_url') . '/weather';
                $currentResponse = Http::get($currentUrl, [
                    'q' => $variant,
                    'appid' => config('services.openweather.api_key'),
                    'units' => 'metric',
                    'lang' => $isFrench ? 'fr' : 'en'
                ]);

                if ($currentResponse->successful()) {
                    $forecastUrl = config('services.openweather.base_url') . '/forecast';
                    $forecastResponse = Http::get($forecastUrl, [
                        'q' => $variant,
                        'appid' => config('services.openweather.api_key'),
                        'units' => 'metric',
                        'lang' => $isFrench ? 'fr' : 'en'
                    ]);

                    if ($forecastResponse->successful()) {
                        $currentData = $currentResponse->json();
                        $forecastData = $forecastResponse->json();
                        return $this->formatWeatherWithForecast($currentData, $forecastData, $isFrench);
                    }
                }
            } catch (\Exception $e) {
                logger()->warning("Failed with variant '$variant': " . $e->getMessage());
                continue; // Try with next variant
            }
        }

        return $isFrench ? "Ville introuvable" : "City not found";
    }

    private function isFrench(string $content): bool
    {
        $frenchKeywords = [
            'météo', 'meteo', 'temps', 'température', 'prévisions', 'previsions',
            'quel', 'quelle', 'comment', 'où', 'ou', 'fait', 'pleut', 'neige',
            'soleil', 'pluie', 'nuages', 'vent', 'chaud', 'froid', 'beau',
            'mauvais', 'aujourd\'hui', 'demain', 'maintenant', 'actuellement',
            'pour', 'dans', 'sur', 'à'
        ];

        $frenchExpressions = [
            'quel temps', 'comment est', 'il fait', 'temps qu\'il fait',
            'temps il fait', 'quelle météo', 'fait-il', 'pleut-il'
        ];

        $content = strtolower($content);

        // Check for french expressions
        foreach ($frenchExpressions as $expression) {
            if (str_contains($content, $expression)) {
                return true;
            }
        }

        // Count words in french
        $frenchCount = 0;
        foreach ($frenchKeywords as $keyword) {
            if (str_contains($content, $keyword)) {
                $frenchCount++;
            }
        }

        return $frenchCount >= 1;
    }

    private function formatWeatherWithForecast(array $currentData, array $forecastData, bool $isFrench = true): string
    {
        // Current weather
        if ($isFrench) {
            $current = sprintf(
                "MAINTENANT à %s: %d°C (ressenti %d°C), %s",
                $currentData['name'],
                round($currentData['main']['temp']),
                round($currentData['main']['feels_like']),
                $currentData['weather'][0]['description']
            );
        } else {
            $current = sprintf(
                "NOW in %s: %d°C (feels like %d°C), %s",
                $currentData['name'],
                round($currentData['main']['temp']),
                round($currentData['main']['feels_like']),
                $currentData['weather'][0]['description']
            );
        }

        // Forecast
        $forecasts = [];
        $processedDays = [];

        foreach ($forecastData['list'] as $forecast) {
            $date = date('Y-m-d', $forecast['dt']);
            $hour = date('H', $forecast['dt']);

            // Forecast at noon for each day
            if ($hour == '12' && !in_array($date, $processedDays) && count($forecasts) < 3) {
                $processedDays[] = $date;

                if ($isFrench) {
                    $dayName = $this->getFrenchDayName($forecast['dt']);
                    $forecasts[] = sprintf(
                        "%s: %d°C, %s",
                        $dayName,
                        round($forecast['main']['temp']),
                        $forecast['weather'][0]['description']
                    );
                } else {
                    $dayName = date('l', $forecast['dt']);
                    $forecasts[] = sprintf(
                        "%s: %d°C, %s",
                        $dayName,
                        round($forecast['main']['temp']),
                        $forecast['weather'][0]['description']
                    );
                }
            }
        }

        $forecastText = implode(', ', $forecasts);

        if ($isFrench) {
            return $current . " | PRÉVISIONS: " . $forecastText;
        } else {
            return $current . " | FORECAST: " . $forecastText;
        }
    }

    private function getFrenchDayName(int $timestamp): string
    {
        $days = [
            'Sunday' => 'Dimanche',
            'Monday' => 'Lundi',
            'Tuesday' => 'Mardi',
            'Wednesday' => 'Mercredi',
            'Thursday' => 'Jeudi',
            'Friday' => 'Vendredi',
            'Saturday' => 'Samedi'
        ];

        $englishDay = date('l', $timestamp);
        return $days[$englishDay] ?? $englishDay;
    }

}
