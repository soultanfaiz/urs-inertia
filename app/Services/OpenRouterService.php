<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenRouterService
{
    protected $apiKey;
    protected $models;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openrouter.api_key');
        $this->models = config('services.openrouter.models', []);
        $this->baseUrl = config('services.openrouter.base_url');
    }

    /**
     * Generate text using OpenRouter API with fallback support.
     */
    public function generate(string $systemPrompt, string $userPrompt, int $maxTokens = 2000): array
    {
        if (empty($this->apiKey) || empty($this->models)) {
            return [
                'success' => false,
                'message' => 'Konfigurasi OpenRouter tidak lengkap.',
            ];
        }

        $lastError = null;
        $usedModel = null;

        foreach ($this->models as $model) {
            try {
                $response = Http::timeout(60)->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'HTTP-Referer' => config('app.url'),
                    'X-Title' => config('app.name'),
                    'Content-Type' => 'application/json',
                ])->post($this->baseUrl . '/chat/completions', [
                            'model' => $model,
                            'messages' => [
                                ['role' => 'system', 'content' => $systemPrompt],
                                ['role' => 'user', 'content' => $userPrompt],
                            ],
                            'max_tokens' => $maxTokens,
                            'temperature' => 0.7,
                        ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return [
                        'success' => true,
                        'data' => $data['choices'][0]['message']['content'] ?? '',
                        'model' => $model,
                    ];
                }

                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'Unknown error';
                $lastError = "Model {$model}: {$errorMessage}";

                // 429 or 5xx -> Try next model
                if ($response->status() === 429 || $response->status() >= 500) {
                    continue;
                }

                // Other 4xx -> Stop
                return [
                    'success' => false,
                    'message' => $errorMessage,
                ];

            } catch (\Exception $e) {
                $lastError = "Model {$model}: " . $e->getMessage();
                continue;
            }
        }

        return [
            'success' => false,
            'message' => 'Semua model AI gagal. ' . $lastError,
        ];
    }
}
