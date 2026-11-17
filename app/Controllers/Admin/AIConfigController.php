<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Env;
use App\Models\AuditLog;
use App\Services\AI\NvidiaAIService;
use Throwable;

class AIConfigController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Tampilan utama halaman Konfigurasi AI & Model
     */
    public function index(): void
    {
        $aiConfig = config('ai');

        $activeProvider = (string)env('AI_PROVIDER', $aiConfig['provider'] ?? 'nvidia');
        $aiEnabled = (bool)env('AI_ENABLED', $aiConfig['enabled'] ?? true);

        // API Keys & Model Per Provider
        $providers = [
            'nvidia' => [
                'name' => 'NVIDIA NIM',
                'api_key' => (string)env('NVIDIA_API_KEY', $aiConfig['nvidia']['api_key'] ?? ''),
                'endpoint' => (string)env('NVIDIA_ENDPOINT', $aiConfig['nvidia']['endpoint'] ?? 'https://integrate.api.nvidia.com/v1/chat/completions'),
                'model' => (string)env('NVIDIA_MODEL', $aiConfig['nvidia']['model'] ?? 'minimaxai/minimax-m3.1-flash'),
                'fallback_model' => (string)env('NVIDIA_FALLBACK_MODEL', $aiConfig['nvidia']['fallback_model'] ?? 'nvidia/nemotron-3.5-lightning-30b-a3b'),
                'temperature' => (float)env('NVIDIA_TEMPERATURE', $aiConfig['nvidia']['temperature'] ?? 0.1),
                'top_p' => (float)env('NVIDIA_TOP_P', $aiConfig['nvidia']['top_p'] ?? 0.7),
                'max_tokens' => (int)env('NVIDIA_MAX_TOKENS', $aiConfig['nvidia']['max_tokens'] ?? 1000),
                'timeout' => (int)env('NVIDIA_TIMEOUT', $aiConfig['nvidia']['timeout'] ?? 8),
            ],
            'gemini' => [
                'name' => 'Google Gemini (Google AI Studio)',
                'api_key' => (string)env('GEMINI_API_KEY', $aiConfig['gemini']['api_key'] ?? ''),
                'endpoint' => (string)env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions'),
                'model' => (string)env('GEMINI_MODEL', $aiConfig['gemini']['model'] ?? 'gemini-3.5-flash-lite'),
                'fallback_model' => (string)env('GEMINI_FALLBACK_MODEL', $aiConfig['gemini']['fallback_model'] ?? 'gemini-flash-lite-latest'),
                'temperature' => 0.1,
                'max_tokens' => 1000,
                'timeout' => 10,
            ],
            'groq' => [
                'name' => 'GroqCloud (Ultra-Fast LPU)',
                'api_key' => (string)env('GROQ_API_KEY', $aiConfig['groq']['api_key'] ?? ''),
                'endpoint' => (string)env('GROQ_ENDPOINT', 'https://api.groq.com/openai/v1/chat/completions'),
                'model' => (string)env('GROQ_MODEL', $aiConfig['groq']['model'] ?? 'qwen/qwen3.8-27b'),
                'fallback_model' => (string)env('GROQ_FALLBACK_MODEL', $aiConfig['groq']['fallback_model'] ?? 'openai/gpt-oss-20b'),
                'temperature' => 0.1,
                'max_tokens' => 1000,
                'timeout' => 10,
            ],
            'openai' => [
                'name' => 'OpenAI',
                'api_key' => (string)env('OPENAI_API_KEY', $aiConfig['openai']['api_key'] ?? ''),
                'endpoint' => (string)env('OPENAI_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
                'model' => (string)env('OPENAI_MODEL', $aiConfig['openai']['model'] ?? 'gpt-4o-mini'),
                'fallback_model' => (string)env('OPENAI_FALLBACK_MODEL', $aiConfig['openai']['fallback_model'] ?? 'gpt-4o'),
                'temperature' => 0.1,
                'max_tokens' => 1000,
                'timeout' => 10,
            ]
        ];

        // Task Models
        $taskModels = [
            'chat' => (string)env('AI_MODEL_CHAT', 'meta/llama-3.2-11b-vision-instruct'),
            'tutor' => (string)env('AI_MODEL_TUTOR', 'meta/llama-3.2-11b-vision-instruct'),
            'vision' => (string)env('AI_MODEL_VISION', 'meta/llama-3.2-11b-vision-instruct'),
            'fallback' => (string)env('AI_MODEL_FALLBACK', 'google/gemma-3-12b-it'),
        ];

        $catalog = $aiConfig['available_models'] ?? [];

        $this->view('admin/ai_config/index', [
            'title' => 'Konfigurasi AI & Model - Super Admin',
            'aiEnabled' => $aiEnabled,
            'activeProvider' => $activeProvider,
            'providers' => $providers,
            'taskModels' => $taskModels,
            'catalog' => $catalog,
            'aiConfig' => $aiConfig,
        ]);
    }

    /**
     * Simpan pembaruan konfigurasi AI dan API Key ke .env
     */
    public function update(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            Session::setFlash('error', 'Token keamanan CSRF tidak valid atau kedaluwarsa.');
            $this->redirect('admin/ai-config');
            return;
        }

        $provider = trim($this->request->string('ai_provider', 'nvidia'));
        $allowedProviders = ['nvidia', 'gemini', 'groq', 'openai'];
        if (!in_array($provider, $allowedProviders, true)) {
            $provider = 'nvidia';
        }

        $aiEnabled = $this->request->string('ai_enabled') === '1' ? 'true' : 'false';

        // Prepare key-values to update in .env
        $envUpdates = [
            'AI_ENABLED' => $aiEnabled,
            'AI_PROVIDER' => $provider,
        ];

        // NVIDIA NIM
        $nvidiaKey = trim($this->request->string('nvidia_api_key'));
        if ($nvidiaKey !== '') {
            $envUpdates['NVIDIA_API_KEY'] = $nvidiaKey;
        }
        $nvidiaModel = trim($this->request->string('nvidia_model'));
        if (!empty($nvidiaModel)) {
            $envUpdates['NVIDIA_MODEL'] = $nvidiaModel;
        }
        $nvidiaFallback = trim($this->request->string('nvidia_fallback_model'));
        if (!empty($nvidiaFallback)) {
            $envUpdates['NVIDIA_FALLBACK_MODEL'] = $nvidiaFallback;
        }

        // Gemini
        $geminiKey = trim($this->request->string('gemini_api_key'));
        $envUpdates['GEMINI_API_KEY'] = $geminiKey;
        $geminiModel = trim($this->request->string('gemini_model'));
        if (!empty($geminiModel)) {
            $envUpdates['GEMINI_MODEL'] = $geminiModel;
        }

        // Groq
        $groqKey = trim($this->request->string('groq_api_key'));
        $envUpdates['GROQ_API_KEY'] = $groqKey;
        $groqModel = trim($this->request->string('groq_model'));
        if (!empty($groqModel)) {
            $envUpdates['GROQ_MODEL'] = $groqModel;
        }

        // OpenAI
        $openaiKey = trim($this->request->string('openai_api_key'));
        $envUpdates['OPENAI_API_KEY'] = $openaiKey;
        $openaiModel = trim($this->request->string('openai_model'));
        if (!empty($openaiModel)) {
            $envUpdates['OPENAI_MODEL'] = $openaiModel;
        }

        // Sampling parameters
        $temp = (float)$this->request->string('nvidia_temperature', '0.1');
        $topP = (float)$this->request->string('nvidia_top_p', '0.7');
        $maxTokens = (int)$this->request->string('nvidia_max_tokens', '1000');
        $timeout = (int)$this->request->string('nvidia_timeout', '8');

        $envUpdates['NVIDIA_TEMPERATURE'] = (string)$temp;
        $envUpdates['NVIDIA_TOP_P'] = (string)$topP;
        $envUpdates['NVIDIA_MAX_TOKENS'] = (string)$maxTokens;
        $envUpdates['NVIDIA_TIMEOUT'] = (string)$timeout;

        // Task Models
        $modelChat = trim($this->request->string('ai_model_chat'));
        if (!empty($modelChat)) $envUpdates['AI_MODEL_CHAT'] = $modelChat;

        $modelTutor = trim($this->request->string('ai_model_tutor'));
        if (!empty($modelTutor)) $envUpdates['AI_MODEL_TUTOR'] = $modelTutor;

        $modelVision = trim($this->request->string('ai_model_vision'));
        if (!empty($modelVision)) $envUpdates['AI_MODEL_VISION'] = $modelVision;

        try {
            $success = Env::update($envUpdates);
            if ($success) {
                // Log Audit
                try {
                    $currentUser = auth();
                    $auditLog = new AuditLog();
                    $auditLog->log(
                        (int)($currentUser['id'] ?? 0),
                        'UPDATE_AI_CONFIG',
                        "Memperbarui konfigurasi AI (Provider: {$provider}, Model: " . ($envUpdates[strtoupper($provider) . '_MODEL'] ?? '-') . ")",
                        ['provider' => $provider]
                    );
                } catch (Throwable $e) {}

                Session::setFlash('success', "Konfigurasi AI & Model berhasil disimpan! Provider aktif sekarang: <strong>" . strtoupper($provider) . "</strong>.");
            } else {
                Session::setFlash('error', 'Gagal menulis ke file .env. Pastikan file memiliki izin tulis (writable).');
            }
        } catch (Throwable $e) {
            Session::setFlash('error', 'Terjadi kesalahan saat menyimpan konfigurasi: ' . $e->getMessage());
        }

        $this->redirect('admin/ai-config');
    }

    /**
     * Endpoint AJAX pengujian koneksi API Key secara live
     * POST /admin/ai-config/test
     */
    public function testConnection(): void
    {
        $token = $this->request->string('_csrf_token');
        if (!csrf_verify($token)) {
            $this->json(['status' => 'error', 'message' => 'Token CSRF tidak valid.'], 403);
            return;
        }

        $provider = trim($this->request->string('provider', 'nvidia'));
        $apiKey = trim($this->request->string('api_key', ''));
        $model = trim($this->request->string('model', ''));

        $endpoints = [
            'nvidia' => 'https://integrate.api.nvidia.com/v1/chat/completions',
            'gemini' => 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions',
            'groq' => 'https://api.groq.com/openai/v1/chat/completions',
            'openai' => 'https://api.openai.com/v1/chat/completions',
        ];

        $defaultModels = [
            'nvidia' => 'minimaxai/minimax-m3.1-flash',
            'gemini' => 'gemini-3.5-flash-lite',
            'groq' => 'qwen/qwen3.8-27b',
            'openai' => 'gpt-4o-mini',
        ];

        if (empty($apiKey)) {
            // Coba ambil dari env jika tidak diisi langsung
            $apiKey = (string)env(strtoupper($provider) . '_API_KEY', '');
        }

        if (empty($apiKey)) {
            $this->json([
                'status' => 'error',
                'message' => "API Key untuk provider {$provider} belum diisi. Masukkan API Key terlebih dahulu."
            ], 422);
            return;
        }

        if (empty($model)) {
            $model = $defaultModels[$provider] ?? 'gpt-4o-mini';
        }

        $endpoint = $endpoints[$provider] ?? $endpoints['nvidia'];

        try {
            $testConfig = [
                'provider' => $provider,
                'api_key' => $apiKey,
                'endpoint' => $endpoint,
                'model' => $model,
                'fallback_model' => '',
                'temperature' => 0.1,
                'top_p' => 0.7,
                'max_tokens' => 120,
                'timeout' => 15,
            ];

            $aiService = new NvidiaAIService($testConfig);
            $reply = $aiService->chatCompletion([
                ['role' => 'user', 'content' => 'Halo! Balas hanya dengan satu kalimat pendek: Koneksi U-Line AI berhasil terhubung.']
            ], [
                'model' => $model,
                'max_tokens' => 120
            ]);

            $this->json([
                'status' => 'success',
                'message' => "Koneksi ke {$provider} berhasil!",
                'provider' => strtoupper($provider),
                'model' => $model,
                'reply' => $reply
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => "Gagal terhubung ke {$provider}: " . $e->getMessage()
            ], 500);
        }
    }
}

