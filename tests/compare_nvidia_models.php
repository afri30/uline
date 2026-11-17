<?php

$apiKey = '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';
$url = 'https://integrate.api.nvidia.com/v1/chat/completions';

$models = [
    'meta/llama-3.2-11b-vision-instruct',
    'meta/llama-3.2-90b-vision-instruct',
    'mistralai/mistral-large-2-instruct'
];

foreach ($models as $m) {
    echo "=== Testing $m ===\n";
    $t0 = microtime(true);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => $m,
        'messages' => [
            ['role' => 'system', 'content' => 'Kamu adalah asisten AI sistem ujian online.'],
            ['role' => 'user', 'content' => 'Jelaskan secara ringkas mengapa 2x + 5 = 15 menghasilkan x = 5.']
        ],
        'temperature' => 0.2,
        'max_tokens' => 120
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $dur = round(microtime(true) - $t0, 2);
    curl_close($ch);

    echo "Status: $code ($dur s)\n";
    $json = json_decode($res, true);
    echo "Output:\n" . ($json['choices'][0]['message']['content'] ?? $res) . "\n\n";
}
