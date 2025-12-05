<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Direct Laravel DB Check ===\n\n";

try {
    // Try to describe quiz_answers table
    echo "Checking quiz_answers table...\n";
    
    $hasTable = Schema::hasTable('quiz_answers');
    echo "Schema::hasTable('quiz_answers'): " . ($hasTable ? 'YES' : 'NO') . "\n\n";
    
    if ($hasTable) {
        $columns = Schema::getColumns('quiz_answers');
        echo "Columns:\n";
        foreach ($columns as $column) {
            echo "  - {$column['name']} ({$column['type_name']})\n";
        }
    }
    
    // List all tables using Schema
    echo "\n=== All Tables via Schema ===\n";
    $connection = Schema::getConnection();
    $tables = $connection->getDoctrineSchemaManager()->listTableNames();
    
    echo "Found " . count($tables) . " tables:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
