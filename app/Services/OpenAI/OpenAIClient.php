<?php

namespace App\Services\OpenAI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIClient
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $defaultModel;
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = config('openai.api_key', '');
        $this->baseUrl = config('openai.base_url', 'https://api.openai.com/v1');
        $this->defaultModel = config('openai.default_model', 'gpt-4o-mini');
        $this->timeout = config('openai.timeout', 30);
    }

    /**
     * Generate text using OpenAI API.
     *
     * @param string $prompt
     * @param array $options
     * @return string|null
     */
    public function generateText(string $prompt, array $options = []): ?string
    {
        if (empty($this->apiKey)) {
            Log::warning('OpenAI API key is not configured');
            return null;
        }

        $model = $options['model'] ?? $this->defaultModel;
        $url = "{$this->baseUrl}/chat/completions";

        try {
            $messages = [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ];

            // Add system message if provided
            if (isset($options['system'])) {
                array_unshift($messages, [
                    'role' => 'system',
                    'content' => $options['system'],
                ]);
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])
                ->post($url, [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => $options['temperature'] ?? 0.7,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Extract text from response
                if (isset($data['choices'][0]['message']['content'])) {
                    return $data['choices'][0]['message']['content'];
                }
            }

            Log::error('OpenAI API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('OpenAI API exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Generate JSON response using OpenAI API.
     *
     * @param string $prompt
     * @param array $schema
     * @param array $options
     * @return array|null
     */
    public function generateJson(string $prompt, array $schema, array $options = []): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('OpenAI API key is not configured');
            return null;
        }

        $model = $options['model'] ?? $this->defaultModel;
        $url = "{$this->baseUrl}/chat/completions";

        // Enhance prompt to request JSON format
        $jsonPrompt = $prompt . "\n\nPlease respond with valid JSON only, matching this structure: " . json_encode($schema, JSON_PRETTY_PRINT);

        try {
            $messages = [
                [
                    'role' => 'user',
                    'content' => $jsonPrompt,
                ],
            ];

            // Add system message if provided
            if (isset($options['system'])) {
                array_unshift($messages, [
                    'role' => 'system',
                    'content' => $options['system'],
                ]);
            }

            $requestBody = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $options['temperature'] ?? 0.7,
            ];
            
            // Note: We don't use response_format json_object here because we need arrays,
            // and json_object forces a single object. The prompt ensures JSON output.

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])
                ->post($url, $requestBody);

            if ($response->successful()) {
                $data = $response->json();

                // Extract text from response
                $text = null;
                if (isset($data['choices'][0]['message']['content'])) {
                    $text = $data['choices'][0]['message']['content'];
                }

                if ($text) {
                    // Try to parse JSON from the response
                    // Sometimes OpenAI wraps JSON in markdown code blocks
                    $text = preg_replace('/```json\s*/', '', $text);
                    $text = preg_replace('/```\s*/', '', $text);
                    $text = trim($text);

                    $json = json_decode($text, true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $json;
                    }

                    Log::warning('OpenAI returned non-JSON response', ['text' => $text]);
                }
            }

            Log::error('OpenAI API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('OpenAI API exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Generate text with conversation history using OpenAI API.
     *
     * @param string $systemInstruction System instruction/context
     * @param array $historyMessages Array of conversation messages with 'role' and 'content' or 'parts'
     * @param array $currentMessage Current user message with 'role' and 'content' or 'parts'
     * @param array $options
     * @return string|null
     */
    public function generateTextWithHistory(string $systemInstruction, array $historyMessages, array $currentMessage, array $options = []): ?string
    {
        if (empty($this->apiKey)) {
            Log::warning('OpenAI API key is not configured');
            return null;
        }

        $model = $options['model'] ?? $this->defaultModel;
        $url = "{$this->baseUrl}/chat/completions";

        try {
            $messages = [];

            // Add system instruction if provided
            if (!empty($systemInstruction)) {
                $messages[] = [
                    'role' => 'system',
                    'content' => $systemInstruction,
                ];
            }

            // Convert history messages to OpenAI format
            foreach ($historyMessages as $msg) {
                $role = $msg['role'] ?? 'user';
                // Convert 'model' role to 'assistant' for OpenAI
                if ($role === 'model') {
                    $role = 'assistant';
                }

                // Extract content from 'parts' array (for backward compatibility) or use 'content' directly
                $content = '';
                if (isset($msg['parts']) && is_array($msg['parts'])) {
                    // Extract text from parts array
                    foreach ($msg['parts'] as $part) {
                        if (isset($part['text'])) {
                            $content .= $part['text'];
                        }
                    }
                } elseif (isset($msg['content'])) {
                    $content = $msg['content'];
                } elseif (isset($msg['text'])) {
                    $content = $msg['text'];
                }

                if (!empty($content)) {
                    $messages[] = [
                        'role' => $role,
                        'content' => $content,
                    ];
                }
            }

            // Add current user message
            if (!empty($currentMessage)) {
                $role = $currentMessage['role'] ?? 'user';
                if ($role === 'model') {
                    $role = 'assistant';
                }

                // Extract content from 'parts' array or use 'content' directly
                $content = '';
                if (isset($currentMessage['parts']) && is_array($currentMessage['parts'])) {
                    foreach ($currentMessage['parts'] as $part) {
                        if (isset($part['text'])) {
                            $content .= $part['text'];
                        }
                    }
                } elseif (isset($currentMessage['content'])) {
                    $content = $currentMessage['content'];
                } elseif (isset($currentMessage['text'])) {
                    $content = $currentMessage['text'];
                }

                if (!empty($content)) {
                    $messages[] = [
                        'role' => $role,
                        'content' => $content,
                    ];
                }
            }

            $requestBody = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $options['temperature'] ?? 0.7,
            ];

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])
                ->post($url, $requestBody);

            if ($response->successful()) {
                $data = $response->json();

                // Extract text from response
                if (isset($data['choices'][0]['message']['content'])) {
                    return $data['choices'][0]['message']['content'];
                }
            }

            // Handle rate limit (429) errors specifically
            if ($response->status() === 429) {
                $errorBody = $response->json();
                $retryAfter = $response->header('Retry-After');

                Log::warning('OpenAI API rate limit exceeded', [
                    'status' => 429,
                    'retry_after' => $retryAfter,
                    'model' => $model,
                    'message' => $errorBody['error']['message'] ?? 'Rate limit exceeded',
                ]);

                // Return null to trigger fallback
                return null;
            }

            Log::error('OpenAI API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('OpenAI API exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Summarize text using OpenAI API.
     *
     * @param string $text
     * @param string $instruction
     * @param int $maxChars
     * @return string|null
     */
    public function summarize(string $text, string $instruction = '', int $maxChars = 600): ?string
    {
        $prompt = "Summarize the following learning resource in a clear, student-friendly way. Limit to about 2–3 sentences.";

        if (!empty($instruction)) {
            $prompt .= " {$instruction}";
        }

        $prompt .= "\n\nTEXT:\n{$text}";

        return $this->generateText($prompt);
    }
}

