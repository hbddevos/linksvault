<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GlmService
{
    /**
     * Base URL for Z.ai API
     */
    protected string $baseUrl = 'https://api.z.ai/api/paas/v4';

    /**
     * Default model to use, glm-4.5-flash, GLM-4.6V-Flash, GLM-4.7-Flash
     */
    protected string $defaultModel = 'glm-4.5-flash';

    /**
     * API Key for authentication
     */
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.glm.api_key', env('GLM_API_KEY'));
        
        if (empty($this->apiKey)) {
            Log::warning('GLM API key is not configured');
        }
    }

    /**
     * Make a chat completion request with conversation history
     *
     * @param array $messages Array of message objects with 'role' and 'content'
     * @param string|null $model Model to use (defaults to glm-5.1)
     * @param string $language Language preference (default: en-US,en)
     * @return array Response from the API
     */
    public function chatWithHistory(array $messages, ?string $model = null, string $language = 'en-US,en'): array
    {
        $model = $model ?? $this->defaultModel;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept-Language' => $language,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => $messages,
            ]);

            if ($response->failed()) {
                Log::error('GLM API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \Exception("GLM API request failed with status {$response->status()}");
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('GLM API chat with history error', [
                'message' => $e->getMessage(),
                'model' => $model,
            ]);

            throw $e;
        }
    }

    /**
     * Make a streaming chat completion request
     *
     * @param array $messages Array of message objects with 'role' and 'content'
     * @param callable $callback Callback function to handle each chunk
     * @param string|null $model Model to use (defaults to glm-5.1)
     * @param string $language Language preference (default: en-US,en)
     * @return void
     */
    public function chatStream(array $messages, callable $callback, ?string $model = null, string $language = 'en-US,en'): void
    {
        $model = $model ?? $this->defaultModel;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept-Language' => $language,
                'Content-Type' => 'application/json',
            ])->withOptions([
                'stream' => true,
            ])->post("{$this->baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => $messages,
                'stream' => true,
            ]);

            if ($response->failed()) {
                Log::error('GLM API streaming request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \Exception("GLM API streaming request failed with status {$response->status()}");
            }

            // Process the stream
            $body = $response->body();
            $lines = explode("\n", $body);

            foreach ($lines as $line) {
                $line = trim($line);
                
                if (empty($line)) {
                    continue;
                }

                // Skip comments
                if (str_starts_with($line, ':')) {
                    continue;
                }

                // Parse SSE data
                if (str_starts_with($line, 'data: ')) {
                    $data = substr($line, 6);
                    
                    // Check for end of stream
                    if ($data === '[DONE]') {
                        break;
                    }

                    try {
                        $chunk = json_decode($data, true);
                        if ($chunk) {
                            $callback($chunk);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to parse streaming chunk', [
                            'data' => $data,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('GLM API streaming error', [
                'message' => $e->getMessage(),
                'model' => $model,
            ]);

            throw $e;
        }
    }

    /**
     * Make a simple chat completion request
     *
     * @param string $message User message content
     * @param string|null $model Model to use (defaults to glm-5.1)
     * @param string $language Language preference (default: en-US,en)
     * @return array Response from the API
     */
    public function chatSimple(string $message, ?string $model = null, string $language = 'en-US,en'): array
    {
        $model = $model ?? $this->defaultModel;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept-Language' => $language,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $message,
                    ],
                ],
            ]);

            if ($response->failed()) {
                Log::error('GLM API simple chat request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \Exception("GLM API simple chat request failed with status {$response->status()}");
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('GLM API simple chat error', [
                'message' => $e->getMessage(),
                'model' => $model,
            ]);

            throw $e;
        }
    }

    /**
     * Extract assistant's response content from API response
     *
     * @param array $response API response
     * @return string|null Assistant's message content
     */
    public function extractResponse(array $response): ?string
    {
        return $response['choices'][0]['message']['content'] ?? null;
    }
}
