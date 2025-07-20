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

        return collect($messages)->map(function ($message) use ($user) {
            if ($message['role'] === 'user') {
                $content = $message['content'];
                $originalContent = $content;

                // Look for weather command in message
                if (str_starts_with(trim($content), '/weather')) {
                    $location = trim(str_replace('/weather', '', $content));

                    if (empty($location)) {
                        $message['content'] = "Answer to the user: To get the weather for a specific location, please type '/weather [city]'";
                        return $message;
                    }

                    $isInFrench = $this->isFrench($originalContent);
                    $weatherData = $this->getWeatherData($location, $isInFrench);

                    if ($isInFrench) {
                        $message['content'] = "Voici la météo pour {$location}: {$weatherData}";
                    } else {
                        $message['content'] = "Here's the weather for {$location}: {$weatherData}";
                    }
                    return $message;
                }

                // Auto-detect weather-related prompts
                if ($this->isWeatherRequest($content)) {
                    $location = $this->extractLocationFromWeatherRequest($content);

                    if ($location) {
                        $isInFrench = $this->isFrench($originalContent);
                        $weatherData = $this->getWeatherData($location, $isInFrench); // ⬅️ Passer le paramètre

                        if ($isInFrench) {
                            $message['content'] = "L'utilisateur demande la météo. Voici les données pour {$location}: {$weatherData}. Présente ces informations de manière conversationnelle en français.";
                        } else {
                            $message['content'] = "User asks for weather. Here's the data for {$location}: {$weatherData}. Present this information in a conversational way in english.";
                        }
                        return $message;
                    }
                }

                // Look for other commands in message
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

    private function getWeatherData(string $location, bool $isFrench = true): string
    {
        try {
            $url = config('services.openweather.base_url') . '/weather';

            $response = Http::get($url, [
                'q' => $location,
                'appid' => config('services.openweather.api_key'),
                'units' => 'metric',
                'lang' => $isFrench ? 'fr' : 'en'
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($isFrench) {
                    return sprintf(
                        "%s, température: %d°C, ressenti: %d°C, %s",
                        $data['name'],
                        round($data['main']['temp']),
                        round($data['main']['feels_like']),
                        $data['weather'][0]['description']
                    );
                } else {
                    return sprintf(
                        "%s, temperature: %d°C, feels like: %d°C, %s",
                        $data['name'],
                        round($data['main']['temp']),
                        round($data['main']['feels_like']),
                        $data['weather'][0]['description']
                    );
                }
            }

            return "Weather API error: " . $response->status() . " - " . $response->body();

        } catch (\Exception $e) {
            logger()->error('Weather API error: ' . $e->getMessage());
            return "Error: " . $e->getMessage();
        }
    }

    private function isWeatherRequest(string $content): bool
    {
        $weatherKeywords = [
            // French
            'météo', 'meteo', 'temps', 'température', 'temperature', 'climat',
            'prévisions', 'previsions', 'forecast', 'pluie', 'pluvieux', 'pleut',
            'soleil', 'ensoleillé', 'ensoleille', 'beau temps', 'nuageux', 'nuages',
            'vent', 'venteux', 'orage', 'orageux', 'neige', 'neigeux', 'neige',
            'brouillard', 'brumeux', 'humide', 'humidité', 'sec', 'chaud', 'froid',
            'frais', 'doux', 'glacial', 'canicule', 'gel', 'verglas',

            // English
            'weather', 'climate', 'temperature', 'temp', 'forecast', 'rain', 'rainy',
            'raining', 'sun', 'sunny', 'sunshine', 'cloud', 'cloudy', 'clouds',
            'wind', 'windy', 'storm', 'stormy', 'snow', 'snowy', 'snowing',
            'fog', 'foggy', 'humid', 'humidity', 'dry', 'hot', 'cold', 'cool',
            'warm', 'freezing', 'heatwave', 'frost', 'ice', 'icy'
        ];

        $weatherExpressions = [
            // French
            'quel temps', 'comment est le temps',
            'il fait quel temps', 'temps qu\'il fait', 'temps il fait',
            'quelle météo', 'comment est la météo', 'c\'est quoi la météo',
            'il fait beau', 'il fait mauvais', 'il pleut', 'il neige',
            'fait-il', 'pleut-il', 'neige-t-il', 'y a-t-il du soleil',

            // English
            'what\'s the weather', 'how\'s the weather', 'what is the weather',
            'how is the weather', 'weather like', 'is it raining', 'is it sunny',
            'is it cold', 'is it hot', 'is it snowing', 'does it rain',
            'will it rain', 'gonna rain', 'going to rain'
        ];

        $content = strtolower($content);

        // Checks for complete exressions first
        $hasWeatherExpression = collect($weatherExpressions)->some(function($expression) use ($content) {
            return str_contains($content, $expression);
        });

        if ($hasWeatherExpression) {
            return true;
        }

        // Checks location keywords
        $hasWeatherKeyword = collect($weatherKeywords)->some(function($keyword) use ($content) {
            return str_contains($content, $keyword);
        });

        $locationKeywords = ['à', 'a', 'in', 'at', 'pour', 'for', 'sur', 'on', 'dans', 'en'];
        $hasLocationIndicator = collect($locationKeywords)->some(function($keyword) use ($content) {
            return str_contains($content, " $keyword ") || str_contains($content, $keyword . " ");
        });

        return $hasWeatherKeyword && $hasLocationIndicator;
    }

    private function extractLocationFromWeatherRequest(string $content): ?string
    {
        $content = strtolower($content);

        $patterns = [
            // French patterns
            '/(?:météo|meteo|temps|température|temperature|climat).*?(?:à|pour|sur|dans|en)\s+([a-záàâäéèêëïîôöùûüÿç\-\s]+?)(?:\s|$|\?|!|,|\.)/i',
            '/(?:quel temps|comment est|quelle météo).*?(?:à|pour|sur|dans|en)\s+([a-záàâäéèêëïîôöùûüÿç\-\s]+?)(?:\s|$|\?|!|,|\.)/i',
            '/(?:temps qu\'il fait|temps il fait).*?(?:à|pour|sur|dans|en)\s+([a-záàâäéèêëïîôöùûüÿç\-\s]+?)(?:\s|$|\?|!|,|\.)/i',
            '/(?:à|pour|sur|dans|en)\s+([a-záàâäéèêëïîôöùûüÿç\-\s]+?).*?(?:météo|meteo|temps|température|temperature|il fait)/i',

            // English patterns
            '/(?:weather|temperature|temp|climate).*?(?:in|at|for|on)\s+([a-zA-Z\-\s]+?)(?:\s|$|\?|!|,|\.)/i',
            '/(?:what\'s|how\'s|what is|how is).*?(?:weather|temperature).*?(?:in|at|for|on)\s+([a-zA-Z\-\s]+?)(?:\s|$|\?|!|,|\.)/i',
            '/(?:in|at|for|on)\s+([a-zA-Z\-\s]+?).*?(?:weather|temperature|temp|climate|raining|sunny|cold|hot)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $location = trim($matches[1]);
                $location = $this->cleanLocation($location);
                if (!empty($location)) {
                    logger()->info('Extracted location: ' . $location . ' from: ' . $content);
                    return $location;
                }
            }
        }

        return null;
    }

    private function cleanLocation(string $location): string
    {
        $stopWords = [
            // Français
            'il', 'fait', 'est', 'sera', 'demain', 'aujourd\'hui', 'hui', 'comme',
            'quel', 'quelle', 'comment', 'maintenant', 'actuellement', 'en ce moment',
            'dehors', 'là',

            // Anglais
            'it', 'is', 'will', 'be', 'today', 'tomorrow', 'now', 'currently',
            'right', 'outside', 'like', 'there', 'here'
        ];

        $words = explode(' ', $location);
        $cleanWords = array_filter($words, function($word) use ($stopWords) {
            $word = trim($word, '?!,.');
            return !in_array(strtolower($word), $stopWords) && strlen($word) > 1;
        });

        $result = trim(implode(' ', $cleanWords));
        return $result;
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

}
