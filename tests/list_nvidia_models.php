<?php

$apiKey = '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';
$url = 'https://integrate.api.nvidia.com/v1/models';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$models = [];
if (isset($data['data'])) {
    foreach ($data['data'] as $m) {
        $models[] = $m['id'];
    }
}
print_r($models);

// Test first 3 text models for completion
$testChatUrl = 'https://integrate.api.nvidia.com/v1/chat/completions';
$candidates = [
    'meta/llama-3.2-11b-vision-instruct',
    'meta/llama-3.2-90b-vision-instruct',
    'meta/llama-3.2-3b-instruct',
    'meta/llama-3.2-1b-instruct',
    'google/gemma-3-12b-it',
    'deepseek-ai/deepseek-v4.1-flash',
    'mistralai/mistral-large-2-instruct',
    'qwen/qwen2.5-72b-instruct'
];

foreach ($candidates as $cand) {
    if (!in_array($cand, $models)) {
        continue;
    }
    echo "Testing Candidate: $cand ...\n";
    $ch = curl_init($testChatUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => $cand,
        'messages' => [
            ['role' => 'user', 'content' => 'Sebutkan 3 fitur utama ujian online dalam bahasa Indonesia.']
        ],
        'temperature' => 0.2,
        'max_tokens' => 150
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "Code: $code\n";
    echo "Body: $res\n\n";
    if ($code === 200) {
        echo "BEST WORKING MODEL: $cand\n";
        break;
    }
}
