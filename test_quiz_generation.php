<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\StudyCard;
use App\Services\QuizService;

echo "=== Test Quiz Generation ===\n\n";

try {
    // Get first user
    $user = User::first();
    if (!$user) {
        echo "No users found. Please register a user first.\n";
        exit(1);
    }
    echo "✓ Found user: {$user->name} (ID: {$user->id})\n";
    
    // Get first study card
    $studyCard = StudyCard::where('user_id', $user->id)->first();
    if (!$studyCard) {
        echo "No study cards found for user. Please create a study card first.\n";
        exit(1);
    }
    echo "✓ Found study card: {$studyCard->title} (ID: {$studyCard->id})\n";
    
    // Check material content
    $contentLength = strlen($studyCard->material_content ?? '');
    echo "✓ Material content length: {$contentLength} characters\n\n";
    
    if ($contentLength < 50) {
        echo "⚠ Warning: Material content is very short. Quiz generation may not work well.\n\n";
    }
    
    echo "Testing QuizService...\n";
    $quizService = app(\App\Services\QuizService::class);
    
    echo "Generating quiz with 3 questions...\n";
    echo "(This may take 15-30 seconds)\n\n";
    
    $startTime = microtime(true);
    $quiz = $quizService->generateQuizFromAI($studyCard->id, ['question_count' => 3]);
    $duration = round(microtime(true) - $startTime, 2);
    
    echo "✓ Quiz generated in {$duration} seconds\n";
    echo "  Quiz ID: {$quiz->id}\n";
    echo "  Title: {$quiz->title}\n";
    
    // Load questions with answers
    $quiz->load('questions.answers');
    $questionCount = $quiz->questions->count();
    
    echo "  Questions: {$questionCount}\n\n";
    
    foreach ($quiz->questions as $index => $question) {
        echo "Question " . ($index + 1) . ": {$question->question_text}\n";
        echo "  Answers:\n";
        foreach ($question->answers as $answer) {
            $mark = $answer->is_correct ? '✓' : ' ';
            echo "    [{$mark}] {$answer->answer_text}\n";
        }
        echo "\n";
    }
    
    echo "✅ TEST SUCCESSFUL!\n";
    echo "Database tables are working correctly.\n";
    echo "You can now test from Flutter app.\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
