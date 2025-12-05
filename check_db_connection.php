<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Database Connection Info ===\n\n";

$config = config('database.connections.pgsql');

echo "Host: " . ($config['host'] ?? 'N/A') . "\n";
echo "Port: " . ($config['port'] ?? 'N/A') . "\n";
echo "Database: " . ($config['database'] ?? 'N/A') . "\n";
echo "Username: " . ($config['username'] ?? 'N/A') . "\n";

echo "\n=== Checking Migrations Table ===\n";

try {
    $migrations = DB::table('migrations')
        ->where('migration', 'like', '%quiz%')
        ->orderBy('id')
        ->get(['id', 'migration', 'batch']);
    
    echo "Found " . count($migrations) . " quiz migrations in 'migrations' table:\n\n";
    foreach ($migrations as $m) {
        echo "[{$m->batch}] {$m->migration}\n";
    }
    
    echo "\n=== Checking All Tables in Current Database ===\n";
    
    $allTables = DB::select("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        ORDER BY table_name
    ");
    
    echo "\nAll tables in public schema:\n";
    foreach ($allTables as $table) {
        echo "  - " . $table->table_name . "\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
