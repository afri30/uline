<?php

$apiKey = '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';
$url = 'https://integrate.api.nvidia.com/v1/chat/completions';

$modelsToTest = [
    'meta/llama-3.3-70b-instruct',
    'meta/llama-3.1-70b-instruct',
    'meta/llama-3.1-8b-instruct',
    'nvidia/llama-3.1-nemotron-70b-instruct'
];

foreach ($modelsToTest as $model) {
    echo "Testing model: $model ...\n";
    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => 'Halo! Jawab singkat dalam 5 kata.']
        ],
        'temperature' => 0.3,
        'max_tokens' => 50
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local cURL testing
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "HTTP Code: $httpCode\n";
    echo "Response: $response\n\n";
    if ($httpCode === 200) {
        echo "SUCCESS WITH MODEL: $model\n";
        break;
    }
}
