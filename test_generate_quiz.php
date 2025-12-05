<?php

// Test Generate Quiz Endpoint

require __DIR__ . '/vendor/autoload.php';

$baseUrl = 'http://192.168.0.105:8000/api';

echo "=== Testing Generate Quiz Endpoint ===\n\n";

// Step 1: Get auth token (you need to replace with real token)
echo "Note: Make sure you have logged in from Flutter and have a valid token\n";
echo "      and at least 1 study card created.\n\n";

// Example: Test with study card ID 1
$studyCardId = 1;
$token = 'your_auth_token_here'; // Replace this with actual token from flutter_secure_storage

echo "Testing: POST /api/study-cards/{$studyCardId}/generate-quiz\n";
echo "Token: " . substr($token, 0, 20) . "...\n\n";

$data = [
    'question_count' => 3
];

$ch = curl_init("{$baseUrl}/study-cards/{$studyCardId}/generate-quiz");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    "Authorization: Bearer {$token}"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n\n";

$result = json_decode($response, true);

if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ SUCCESS!\n\n";
    echo "Response:\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "❌ Error:\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
    if ($httpCode === 401) {
        echo "\n⚠️  Authentication failed. Please:\n";
        echo "   1. Login from Flutter app\n";
        echo "   2. Get the token from flutter_secure_storage\n";
        echo "   3. Replace 'your_auth_token_here' in this script\n";
    }
}

echo "\n=== Test Complete ===\n";
