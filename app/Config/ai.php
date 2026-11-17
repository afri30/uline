<?php

declare(strict_types=1);

use App\Core\Env;

if (class_exists(Env::class)) {
    Env::load();
}

/**
 * AI Provider & Strict Guardrails Configuration
 * Provider: NVIDIA NIM (National & Enterprise AI Model Endpoints)
 */

$apiKey = function_exists('env') 
    ? (string)env('NVIDIA_API_KEY', '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') 
    : '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';

$endpoint = function_exists('env')
    ? (string)env('NVIDIA_ENDPOINT', 'https://integrate.api.nvidia.com/v1/chat/completions')
    : 'https://integrate.api.nvidia.com/v1/chat/completions';

$model = function_exists('env')
    ? (string)env('NVIDIA_MODEL', 'minimaxai/minimax-m3.1-flash')
    : 'minimaxai/minimax-m3.1-flash';

$fallbackModel = function_exists('env')
    ? (string)env('NVIDIA_FALLBACK_MODEL', 'nvidia/nemotron-3.5-lightning-30b-a3b')
    : 'nvidia/nemotron-3.5-lightning-30b-a3b';

$temperature = function_exists('env')
    ? (float)env('NVIDIA_TEMPERATURE', 0.1)
    : 0.1;

$topP = function_exists('env')
    ? (float)env('NVIDIA_TOP_P', 0.7)
    : 0.7;

$maxTokens = function_exists('env')
    ? (int)env('NVIDIA_MAX_TOKENS', 1000)
    : 1000;

$timeout = function_exists('env')
    ? (int)env('NVIDIA_TIMEOUT', 10)
    : 10;


return [
    'enabled' => function_exists('env') ? (bool)env('AI_ENABLED', true) : true,
    'provider' => function_exists('env') ? (string)env('AI_PROVIDER', 'nvidia') : 'nvidia',
    
    // --------------------------------------------------------------------------
    // 1. Primary AI Provider: NVIDIA NIM (Active Engine)
    // --------------------------------------------------------------------------
    'nvidia' => [
        'api_key' => $apiKey,
        'endpoint' => $endpoint,
        'model' => $model,
        'fallback_model' => $fallbackModel,
        'temperature' => $temperature, // Low temperature for high adherence to domain constraints
        'top_p' => $topP,
        'max_tokens' => $maxTokens,
        'timeout' => $timeout, // seconds
    ],

    // --------------------------------------------------------------------------
    // 2. Alternative AI Providers Configuration (Ready to Switch via AI_PROVIDER)
    // --------------------------------------------------------------------------
    'gemini' => [
        'api_key' => function_exists('env') ? (string)env('GEMINI_API_KEY', '') : '',
        'model' => function_exists('env') ? (string)env('GEMINI_MODEL', 'gemini-3.5-flash-lite') : 'gemini-3.5-flash-lite',
        'fallback_model' => function_exists('env') ? (string)env('GEMINI_FALLBACK_MODEL', 'gemini-flash-lite-latest') : 'gemini-flash-lite-latest',
        'endpoint' => function_exists('env') ? (string)env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions') : 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions',
        'temperature' => 0.1,
        'max_tokens' => 1000,
        'timeout' => 10,
    ],

    'openai' => [
        'api_key' => function_exists('env') ? (string)env('OPENAI_API_KEY', '') : '',
        'model' => function_exists('env') ? (string)env('OPENAI_MODEL', 'gpt-4o-mini') : 'gpt-4o-mini',
        'fallback_model' => function_exists('env') ? (string)env('OPENAI_FALLBACK_MODEL', 'gpt-4o') : 'gpt-4o',
        'endpoint' => function_exists('env') ? (string)env('OPENAI_ENDPOINT', 'https://api.openai.com/v1/chat/completions') : 'https://api.openai.com/v1/chat/completions',
        'temperature' => 0.1,
        'max_tokens' => 1000,
        'timeout' => 10,
    ],

    'groq' => [
        'api_key' => function_exists('env') ? (string)env('GROQ_API_KEY', '') : '',
        'model' => function_exists('env') ? (string)env('GROQ_MODEL', 'qwen/qwen3.8-27b') : 'qwen/qwen3.8-27b',
        'fallback_model' => function_exists('env') ? (string)env('GROQ_FALLBACK_MODEL', 'openai/gpt-oss-20b') : 'openai/gpt-oss-20b',
        'endpoint' => function_exists('env') ? (string)env('GROQ_ENDPOINT', 'https://api.groq.com/openai/v1/chat/completions') : 'https://api.groq.com/openai/v1/chat/completions',
        'temperature' => 0.1,
        'max_tokens' => 1000,
        'timeout' => 10,
    ],

    // --------------------------------------------------------------------------
    // 3. Task-Specific Model Mapping (Modular Assignment)
    // --------------------------------------------------------------------------
    'task_models' => [
        'assistant' => function_exists('env') ? (string)env('AI_MODEL_CHAT', $model) : $model,
        'tutor' => function_exists('env') ? (string)env('AI_MODEL_TUTOR', $model) : $model,
        'proctoring_vision' => function_exists('env') ? (string)env('AI_MODEL_VISION', $model) : $model,
        'fallback' => function_exists('env') ? (string)env('AI_MODEL_FALLBACK', $fallbackModel) : $fallbackModel,
    ],

    // --------------------------------------------------------------------------
    // 4. Model Catalog & Capability Matrix (Pilihan Model yang Didukung)
    // --------------------------------------------------------------------------
    'available_models' => [
        'nvidia' => [
            [
                'id' => 'minimaxai/minimax-m3.1-flash',
                'name' => 'MiniMax M3.1 Flash',
                'provider' => 'NVIDIA NIM / MiniMax',
                'category' => 'Ultra-Fast Flash Model',
                'type' => 'default_primary',
                'description' => 'Model respons ultra cepat dengan penalaran cerdas dan latensi rendah pada endpoint NVIDIA NIM.',
                'context_window' => '128k tokens',
                'vision_support' => false,
            ],
            [
                'id' => 'nvidia/nemotron-3.5-lightning-30b-a3b',
                'name' => 'NVIDIA Nemotron 3.5 Lightning 30B',
                'provider' => 'NVIDIA NIM',
                'category' => 'High-Throughput Reasoning',
                'type' => 'default_fallback',
                'description' => 'Arsitektur Mixture-of-Experts 30B bertenaga tinggi untuk penalaran akademis akurat.',
                'context_window' => '128k tokens',
                'vision_support' => false,
            ],
            [
                'id' => 'meta/llama-guard-4-12b',
                'name' => 'Llama Guard 4 12B',
                'provider' => 'NVIDIA NIM / Meta',
                'category' => 'Academic Safety & Guardrail',
                'type' => 'optional',
                'description' => 'Model perlindungan keamanan konten dan pendeteksi pelanggaran ujian proctoring.',
                'context_window' => '128k tokens',
                'vision_support' => false,
            ],
            [
                'id' => 'meta/llama-3.3-70b-instruct',
                'name' => 'Llama 3.3 70B Instruct',
                'provider' => 'NVIDIA NIM / Meta',
                'category' => 'High-tier Reasoning',
                'type' => 'optional',
                'description' => 'Kapasitas penalaran enterprise untuk analisis butir soal ujian tingkat lanjut dan evaluasi.',
                'context_window' => '128k tokens',
                'vision_support' => false,
            ],
            [
                'id' => 'meta/llama-3.1-8b-instruct',
                'name' => 'Llama 3.1 8B Instruct',
                'provider' => 'NVIDIA NIM / Meta',
                'category' => 'Ultra-Fast Lightweight',
                'type' => 'optional',
                'description' => 'Latensi sangat rendah dengan performa respons sekejap untuk asisten tanya jawab cepat.',
                'context_window' => '128k tokens',
                'vision_support' => false,
            ],
            [
                'id' => 'deepseek-ai/deepseek-r1',
                'name' => 'DeepSeek R1',
                'provider' => 'NVIDIA NIM / DeepSeek',
                'category' => 'Deep STEM Reasoning',
                'type' => 'optional',
                'description' => 'Penalaran langkah-demi-langkah mendalam untuk soal eksakta (Matematika & Fisika).',
                'context_window' => '64k tokens',
                'vision_support' => false,
            ],
            [
                'id' => 'qwen/qwen2.5-72b-instruct',
                'name' => 'Qwen 2.5 72B Instruct',
                'provider' => 'NVIDIA NIM / Alibaba',
                'category' => 'Multilingual & General Knowledge',
                'type' => 'optional',
                'description' => 'Pemahaman bahasa Indonesia dan pengetahuan akademis luas berskala besar.',
                'context_window' => '128k tokens',
                'vision_support' => false,
            ],
            [
                'id' => 'mistralai/mixtral-8x7b-instruct-v0.1',
                'name' => 'Mixtral 8x7B Instruct',
                'provider' => 'NVIDIA NIM / Mistral',
                'category' => 'Mixture of Experts',
                'type' => 'optional',
                'description' => 'Arsitektur MoE efisien untuk penjadwalan ujian dan panduan alur fitur.',
                'context_window' => '32k tokens',
                'vision_support' => false,
            ],
        ],
        'gemini' => [
            [
                'id' => 'gemini-3.5-flash-lite',
                'name' => 'Gemini 3.5 Flash-Lite',
                'provider' => 'Google AI Studio',
                'category' => 'Ultra-Fast Multimodal',
                'type' => 'primary',
                'description' => 'Model cepat, hemat kuota, dan sangat responsif untuk asisten chatbot sekolah.',
                'context_window' => '1M tokens',
                'vision_support' => true,
            ],
            [
                'id' => 'gemini-flash-lite-latest',
                'name' => 'Gemini Flash-Lite Latest',
                'provider' => 'Google AI Studio',
                'category' => 'Official Stable Release',
                'type' => 'optional',
                'description' => 'Versi rilis terkini Gemini Flash-Lite dengan optimasi latensi rendah.',
                'context_window' => '1M tokens',
                'vision_support' => true,
            ],
            [
                'id' => 'gemini-3.8-flash',
                'name' => 'Gemini 3.8 Flash',
                'provider' => 'Google AI Studio',
                'category' => 'High-Capacity Multimodal',
                'type' => 'optional',
                'description' => 'Model multimodal generasi baru Google untuk penalaran dan proctoring visual.',
                'context_window' => '1M tokens',
                'vision_support' => true,
            ],
            [
                'id' => 'gemini-pro-latest',
                'name' => 'Gemini Pro Latest',
                'provider' => 'Google AI Studio',
                'category' => 'Deep Academic Reasoning',
                'type' => 'optional',
                'description' => 'Penalaran kompleks dengan konteks besar untuk bank soal dan materi ujian.',
                'context_window' => '2M tokens',
                'vision_support' => true,
            ],
        ],
        'groq' => [
            [
                'id' => 'qwen/qwen3.8-27b',
                'name' => 'Qwen 3.8 27B',
                'provider' => 'GroqCloud',
                'category' => 'Ultra-Fast LPU Inference',
                'type' => 'primary',
                'description' => 'Inferensi ratusan token/detik pada LPU Groq, sangat cerdas dan responsif.',
                'context_window' => '128k tokens',
                'vision_support' => false,
            ],
            [
                'id' => 'openai/gpt-oss-20b',
                'name' => 'GPT-OSS 20B',
                'provider' => 'GroqCloud',
                'category' => 'Efficient Realtime',
                'type' => 'optional',
                'description' => 'Model open-weight ultra cepat untuk interaksi asisten realtime.',
                'context_window' => '64k tokens',
                'vision_support' => false,
            ],
            [
                'id' => 'openai/gpt-oss-120b',
                'name' => 'GPT-OSS 120B',
                'provider' => 'GroqCloud',
                'category' => 'Flagship Large Model',
                'type' => 'optional',
                'description' => 'Model skala besar 120B parameter untuk penalaran butir soal kompleks.',
                'context_window' => '128k tokens',
                'vision_support' => false,
            ],
            [
                'id' => 'allam-2-7b',
                'name' => 'Allam 2 7B',
                'provider' => 'GroqCloud',
                'category' => 'Lightweight Fast',
                'type' => 'optional',
                'description' => 'Model ringkas dan efisien untuk tanya jawab singkat.',
                'context_window' => '32k tokens',
                'vision_support' => false,
            ],
        ],
        'openai' => [
            [
                'id' => 'gpt-4o-mini',
                'name' => 'GPT-4o Mini',
                'provider' => 'OpenAI',
                'category' => 'Efficient Multimodal',
                'type' => 'primary',
                'description' => 'Model cepat, hemat biaya, dan andal untuk interaksi chatbot sekolah.',
                'context_window' => '128k tokens',
                'vision_support' => true,
            ],
            [
                'id' => 'gpt-4o',
                'name' => 'GPT-4o (Omni)',
                'provider' => 'OpenAI',
                'category' => 'Flagship Multimodal',
                'type' => 'optional',
                'description' => 'Model multimodal flagship OpenAI untuk visi dan penalaran mendalam.',
                'context_window' => '128k tokens',
                'vision_support' => true,
            ],
        ],
    ],

    // --------------------------------------------------------------------------
    // 5. Sampling Presets for Academic Integrity & Domain Fidelity
    // --------------------------------------------------------------------------
    'presets' => [
        'strict_proctoring' => [
            'temperature' => 0.0,
            'top_p' => 0.5,
            'label' => 'Strict Examination Proctoring (Zero Hallucination)',
        ],
        'grounded_assistant' => [
            'temperature' => 0.1,
            'top_p' => 0.7,
            'label' => 'Grounded Database Assistant (Factual Adherence)',
        ],
        'interactive_tutor' => [
            'temperature' => 0.2,
            'top_p' => 0.8,
            'label' => 'Pedagogical AI Tutor (Clear & Engaging)',
        ],
    ],

    // Guardrail constraints & security policies
    'guardrails' => [
        'system_identity' => 'U-Line AI Assistant (AI Online Examination & Proctoring System)',
        
        // Disallowed injection, confidential terms, coding/programming, and off-topic categories
        'blocked_patterns' => [
            // Prompt Injection & Jailbreaks
            '/(?:ignore|disregard|forget)\s+(?:all\s+)?(?:previous|prior)\s+(?:instructions|prompts|rules)/i',
            '/(?:system\s+prompt|developer\s+mode|jailbreak|DAN\s+mode|unrestricted\s+mode)/i',
            
            // Database & Credential Injections
            '/(?:SELECT\s+.+\s+FROM|UNION\s+SELECT|DROP\s+TABLE|INSERT\s+INTO|UPDATE\s+\w+\s+SET)/i',
            '/(?:api[_-]?key|password_hash|secret_key|credential|token|cookie)/i',
            
            // Cheating Attempts on Active Exams
            '/(?:kunci\s+jawaban\s+semua|bocorkan\s+soal|bocoran\s+ujian|jawaban\s+nomor\s+\d+\s+apa)/i',

            // STRICT BAN ON PROGRAMMING & CODING
            '/(?:koding|coding|percodingan|pemrograman|source\s*code|skrip\s+program|script\s+program)/i',
            '/(?:buatkan|tuliskan|bikin|contoh|tutorial)\s+(?:kode|koding|coding|script|skrip|program|algoritma|function|class)/i',
            '/(?:debug\s+kode|error\s+coding|perbaiki\s+kodingan|syntax\s+error|cara\s+coding|belajar\s+coding)/i',
            '/(?:bahasa\s+pemrograman|python|javascript|php|golang|typescript|c\+\+|c\#|ruby|rust|kotlin|swift)/i',
            '/(?:console\.log|echo\s+[\'"]|print\s*\(|def\s+\w+\(|public\s+function|import\s+\w+|include\s+[\'"]|<\?php|<\/?html>|<\/?div>)/i',

            // Common Off-Topic General Queries
            '/(?:resep\s+masakan|cara\s+membuat\s+kue|resep\s+makanan|cara\s+masak)/i',
            '/(?:ramalan\s+zodiak|horoskop|ramalan\s+bintang)/i',
            '/(?:buatkan\s+puisi|buatkan\s+pantun|buatkan\s+cerpen|buatkan\s+lagu|lelucon|dongeng)/i',
            '/(?:siapa\s+presiden|politik\s+pemilu|partai\s+politik|pilpres|pilkada)/i',
            '/(?:rekomendasi\s+film|rekomendasi\s+game|jadwal\s+bioskop|skor\s+sepak\s+bola)/i',
            '/(?:cuaca\s+hari\s+ini|prakiraan\s+cuaca)/i',
        ],

        // Forbidden topics standard response
        'forbidden_topics_msg' => 'Maaf, saya adalah asisten khusus untuk aplikasi web U-Line (AI Online Examination & Proctoring System). Saya tidak dapat membahas percodingan/pemrograman maupun topik di luar sistem ujian ini. Saya hanya dapat menjawab pertanyaan seputar penggunaan website ini, fitur-fiturnya, jadwal ujian, dan data resmi sesuai hak akses Anda.',
    ]
];
