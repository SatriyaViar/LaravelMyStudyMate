<?php

return [
    'default' => env('AI_PROVIDER', 'deepseek'),
    
    'providers' => [
        'deepseek' => [
            'api_key' => env('DEEPSEEK_API_KEY'),
            'api_url' => env('DEEPSEEK_API_URL', 'https://api.deepseek.com/v1'),
            'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
            'max_tokens' => env('DEEPSEEK_MAX_TOKENS', 4000),
            'temperature' => env('DEEPSEEK_TEMPERATURE', 0.7),
        ],
    ],
    
    'quiz_generation' => [
        'default_questions' => 10,
        'min_questions' => 5,
        'max_questions' => 50,
    ],
];