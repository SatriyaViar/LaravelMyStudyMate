<?php

require __DIR__ . '/vendor/autoload.php';

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['GEMINI_API_KEY'];
$model = $_ENV['GEMINI_MODEL'] ?? 'gemini-1.5-flash';
$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

echo "Testing Gemini API...\n";
echo "Endpoint: {$endpoint}\n";
echo "API Key: " . substr($apiKey, 0, 20) . "...\n\n";

$data = [
    'contents' => [
        [
            'parts' => [
                ['text' => 'Generate a simple quiz question about mathematics in JSON format with this structure: {"questions":[{"question_text":"What is 2+2?","answers":[{"answer_text":"4","is_correct":true},{"answer_text":"3","is_correct":false}]}]}']
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'topK' => 40,
        'topP' => 0.95,
        'maxOutputTokens' => 8192,
        'responseMimeType' => 'application/json',
    ]
];

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n\n";

if ($error) {
    echo "CURL Error: {$error}\n";
    exit(1);
}

if ($httpCode !== 200) {
    echo "Error Response:\n";
    echo $response . "\n";
    exit(1);
}

$result = json_decode($response, true);

if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    echo "✅ SUCCESS! Gemini API is working!\n\n";
    echo "Response:\n";
    echo $result['candidates'][0]['content']['parts'][0]['text'] . "\n";
} else {
    echo "❌ Unexpected response format:\n";
    print_r($result);
}
