<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Checking Quiz Tables in Database ===\n\n";

try {
    // Get all tables with 'quiz' in name
    $tables = DB::select("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name LIKE '%quiz%' 
        ORDER BY table_name
    ");
    
    echo "Found " . count($tables) . " quiz-related tables:\n";
    foreach ($tables as $table) {
        echo "  - " . $table->table_name . "\n";
    }
    
    echo "\n=== Checking quiz_answers specifically ===\n";
    $quizAnswers = DB::select("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'quiz_answers'
    ");
    
    if (count($quizAnswers) > 0) {
        echo "✓ quiz_answers table EXISTS\n\n";
        
        // Get columns
        $columns = DB::select("
            SELECT column_name, data_type 
            FROM information_schema.columns 
            WHERE table_name = 'quiz_answers' 
            ORDER BY ordinal_position
        ");
        
        echo "Columns in quiz_answers:\n";
        foreach ($columns as $col) {
            echo "  - {$col->column_name} ({$col->data_type})\n";
        }
    } else {
        echo "✗ quiz_answers table DOES NOT EXIST\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
