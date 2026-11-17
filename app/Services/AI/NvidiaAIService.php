<?php

declare(strict_types=1);

namespace App\Services\AI;

use Exception;

class NvidiaAIService
{
    private string $provider = 'nvidia';
    private string $apiKey;
    private string $endpoint;
    private string $model;
    private string $fallbackModel;
    private float $temperature;
    private float $topP;
    private int $maxTokens;
    private int $timeout;

    public function __construct(?array $config = null)
    {
        if ($config === null) {
            $fullConfig = require dirname(__DIR__, 2) . '/Config/ai.php';
            $activeProvider = function_exists('env') ? (string)env('AI_PROVIDER', $fullConfig['provider'] ?? 'nvidia') : ($fullConfig['provider'] ?? 'nvidia');
            $this->provider = $activeProvider;
            $config = $fullConfig[$activeProvider] ?? $fullConfig['nvidia'] ?? [];
        } else {
            $this->provider = (string)($config['provider'] ?? 'nvidia');
        }

        $this->apiKey = (string)($config['api_key'] ?? '');
        $this->endpoint = (string)($config['endpoint'] ?? 'https://integrate.api.nvidia.com/v1/chat/completions');
        $this->model = (string)($config['model'] ?? 'meta/llama-3.2-11b-vision-instruct');
        $this->fallbackModel = (string)($config['fallback_model'] ?? 'google/gemma-3-12b-it');
        $this->temperature = (float)($config['temperature'] ?? 0.1);
        $this->topP = (float)($config['top_p'] ?? 0.7);
        $this->maxTokens = (int)($config['max_tokens'] ?? 600);
        $this->timeout = (int)($config['timeout'] ?? 10);
    }

    /**
     * Get active provider name
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Send chat completion request to AI Provider (NVIDIA NIM / Gemini / Groq / OpenAI)
     *
     * @param array $messages Array of ['role' => 'system'|'user'|'assistant', 'content' => string]
     * @param array $options Optional override options (e.g. temperature, max_tokens, model)
     * @return string Generated assistant reply
     * @throws Exception
     */
    public function chatCompletion(array $messages, array $options = []): string
    {
        if (empty($this->apiKey)) {
            $providerName = strtoupper($this->provider);
            throw new Exception("API Key {$providerName} belum dikonfigurasi. Silakan isi API Key di menu Konfigurasi AI.");
        }

        $model = $options['model'] ?? $this->model;
        $temperature = $options['temperature'] ?? $this->temperature;
        $maxTokens = $options['max_tokens'] ?? $this->maxTokens;

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'top_p' => $this->topP,
            'max_tokens' => $maxTokens,
            'stream' => false
        ];

        $lastException = null;
        $response = null;

        // Try primary model
        try {
            $response = $this->executeCurl($payload);
        } catch (Exception $e) {
            $lastException = $e;
        }

        // If primary model failed or timed out, attempt fallback model
        if (($response === null || $response['code'] !== 200) && !empty($this->fallbackModel) && $model !== $this->fallbackModel) {
            $payload['model'] = $this->fallbackModel;
            try {
                $response = $this->executeCurl($payload);
                $lastException = null;
            } catch (Exception $e) {
                $lastException = $e;
            }
        }

        if ($response === null && $lastException !== null) {
            throw $lastException;
        }

        if ($response['code'] !== 200) {
            $providerLabel = strtoupper($this->provider);
            $errorMsg = "Gagal menghubungi AI Provider ({$providerLabel}). Kode HTTP: {$response['code']}";
            $decoded = json_decode($response['body'], true);
            $extractedErr = null;
            if (is_array($decoded)) {
                if (!empty($decoded['error']['message'])) {
                    $extractedErr = $decoded['error']['message'];
                } elseif (!empty($decoded[0]['error']['message'])) {
                    $extractedErr = $decoded[0]['error']['message'];
                } elseif (!empty($decoded['detail'])) {
                    $extractedErr = is_string($decoded['detail']) ? $decoded['detail'] : json_encode($decoded['detail']);
                } elseif (!empty($decoded['message'])) {
                    $extractedErr = $decoded['message'];
                }
            }
            if ($extractedErr !== null) {
                $errorMsg .= " - " . $extractedErr;
            }
            throw new Exception($errorMsg);
        }

        $data = json_decode($response['body'], true);
        $msgObj = $data['choices'][0]['message'] ?? [];
        $content = !empty($msgObj['content']) ? $msgObj['content'] : ($msgObj['reasoning_content'] ?? null);
        if ($content === null) {
            throw new Exception("Format respon tidak valid dari AI Provider.");
        }

        $cleaned = $this->cleanAIResponse((string)$content);
        if (empty($cleaned)) {
            throw new Exception("AI Provider hanya mengembalikan proses berpikir (thinking process) tanpa jawaban final.");
        }

        return $cleaned;
    }

    /**
     * Clean raw AI response from internal thinking process / chain-of-thought artifacts
     */
    public function cleanAIResponse(string $raw): string
    {
        $text = trim($raw);

        // 1. Remove XML/HTML style reasoning tags: <think>...</think>, <thought>...</thought>, <reasoning>...</reasoning>
        $text = preg_replace('/<(?:think|thought|reasoning)>.*?<\/(?:think|thought|reasoning)>/is', '', $text);
        // Also handle unclosed <think> tag if output was cut off
        $text = preg_replace('/<(?:think|thought|reasoning)>.*$/is', '', $text);

        // 2. Remove markdown/text thinking headers like:
        // "Here's a thinking process: ... Final Answer: ..."
        if (preg_match('/^(?:Here\'?s\s+a\s+thinking\s+process|Here\s+is\s+a\s+thinking\s+process|Thinking\s+Process|Thought\s+Process|Reasoning\s+Process)\s*:?\s*/i', $text)) {
            // If there's an explicit "Final Answer" or "Jawaban" section
            if (preg_match('/(?:^|\n\n|\r\n\r\n)(?:\*\*?|\#\#?\s*)?(?:Final\s*Answer|Jawaban|Response|Respons|Hasil)\*\*?:?\s*[\r\n]+(.*)$/is', $text, $matches)) {
                $text = trim($matches[1]);
            } else {
                // Split by double line breaks and discard initial thinking paragraphs
                $paragraphs = preg_split('/(?:\r?\n){2,}/', $text);
                $realParagraphs = [];
                $inThinking = true;

                foreach ($paragraphs as $p) {
                    $pTrim = trim($p);
                    if (empty($pTrim)) continue;

                    if ($inThinking) {
                        if (preg_match('/^(?:Here\'?s\s+a\s+thinking|Thinking\s+Process|Thought\s+Process|\d+[\.\)]\s*(?:Analyze|Identify|Determine|Consider|Formulate|Recall|Draft|Check|The\s+user|User\s+input))/i', $pTrim)) {
                            continue;
                        }
                        // First paragraph that is not thinking:
                        $inThinking = false;
                    }
                    $realParagraphs[] = $pTrim;
                }
                $text = implode("\n\n", $realParagraphs);
            }
        }

        // 3. Remove leading "Final Answer:" or "Jawaban:" label if leftover
        $text = preg_replace('/^(?:\*\*?|\#\#?\s*)?(?:Final\s*Answer|Jawaban|Response|Respons|Conclusion)\*?:?\s*/i', '', $text);

        return trim($text);
    }

    /**
     * Internal cURL executor
     */
    private function executeCurl(array $payload): array
    {
        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local development flexibility

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            $providerLabel = strtoupper($this->provider);
            throw new Exception("cURL Error ke {$providerLabel} API: " . $curlError);
        }

        return [
            'code' => (int)$code,
            'body' => (string)$body
        ];
    }
}
