<?php

namespace App\Services\Gemini;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiClient
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $defaultModel;
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = config('gemini.api_key', '');
        $this->baseUrl = config('gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta/models');
        $this->defaultModel = config('gemini.default_model', 'gemini-2.0-flash-exp');
        $this->timeout = config('gemini.timeout', 30);
    }

    /**
     * Generate text using Gemini API.
     *
     * @param string $prompt
     * @param array $options
     * @return string|null
     */
    public function generateText(string $prompt, array $options = []): ?string
    {
        if (empty($this->apiKey)) {
            Log::warning('Gemini API key is not configured');
            return null;
        }

        $model = $options['model'] ?? $this->defaultModel;
        $url = "{$this->baseUrl}/{$model}:generateContent";

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $this->apiKey,
                ])
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Extract text from response
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    return $data['candidates'][0]['content']['parts'][0]['text'];
                }
            }

            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Gemini API exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Generate JSON response using Gemini API.
     *
     * @param string $prompt
     * @param array $schema
     * @param array $options
     * @return array|null
     */
    public function generateJson(string $prompt, array $schema, array $options = []): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('Gemini API key is not configured');
            return null;
        }

        $model = $options['model'] ?? $this->defaultModel;
        $url = "{$this->baseUrl}/{$model}:generateContent";

        // Enhance prompt to request JSON format
        $jsonPrompt = $prompt . "\n\nPlease respond with valid JSON only, matching this structure: " . json_encode($schema, JSON_PRETTY_PRINT);

        try {
            $requestBody = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $jsonPrompt],
                        ],
                    ],
                ],
            ];

            // Add response_mime_type if supported by the model
            if (isset($options['response_mime_type']) && $options['response_mime_type'] === 'application/json') {
                $requestBody['generationConfig'] = [
                    'response_mime_type' => 'application/json',
                ];
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $this->apiKey,
                ])
                ->post($url, $requestBody);

            if ($response->successful()) {
                $data = $response->json();

                // Extract text from response
                $text = null;
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $text = $data['candidates'][0]['content']['parts'][0]['text'];
                }

                if ($text) {
                    // Try to parse JSON from the response
                    // Sometimes Gemini wraps JSON in markdown code blocks
                    $text = preg_replace('/```json\s*/', '', $text);
                    $text = preg_replace('/```\s*/', '', $text);
                    $text = trim($text);

                    $json = json_decode($text, true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $json;
                    }

                    Log::warning('Gemini returned non-JSON response', ['text' => $text]);
                }
            }

            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Gemini API exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Generate text with conversation history using Gemini API.
     *
     * @param string $systemInstruction System instruction/context
     * @param array $historyMessages Array of conversation messages with 'role' and 'parts'
     * @param array $currentMessage Current user message with 'role' and 'parts'
     * @param array $options
     * @return string|null
     */
    public function generateTextWithHistory(string $systemInstruction, array $historyMessages, array $currentMessage, array $options = []): ?string
    {
        if (empty($this->apiKey)) {
            Log::warning('Gemini API key is not configured');
            return null;
        }

        $model = $options['model'] ?? $this->defaultModel;
        $url = "{$this->baseUrl}/{$model}:generateContent";

        try {
            // Build contents array with system instruction and conversation history
            $contents = [];

            // Add system instruction as the first user message
            if (!empty($systemInstruction)) {
                $contents[] = [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $systemInstruction],
                    ],
                ];
                // Add a model response to acknowledge system instruction (Gemini expects alternating roles)
                $contents[] = [
                    'role' => 'model',
                    'parts' => [
                        ['text' => 'Understood. I will assist you as EduNexus JPENHS AI assistant.'],
                    ],
                ];
            }

            // Add conversation history (should already be in correct alternating format)
            foreach ($historyMessages as $msg) {
                $contents[] = [
                    'role' => $msg['role'] ?? 'user',
                    'parts' => $msg['parts'] ?? [['text' => '']],
                ];
            }

            // Add current user message
            if (!empty($currentMessage)) {
                $contents[] = [
                    'role' => $currentMessage['role'] ?? 'user',
                    'parts' => $currentMessage['parts'] ?? [['text' => '']],
                ];
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $this->apiKey,
                ])
                ->post($url, [
                    'contents' => $contents,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Extract text from response
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    return $data['candidates'][0]['content']['parts'][0]['text'];
                }
            }

            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Gemini API exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Summarize text using Gemini API.
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

