<?php
// Test Database Connection for Production
// URL: https://laravelmystudymate-main-2ftdg6.laravel.cloud/test-db.php

use Illuminate\Support\Facades\DB;

header('Content-Type: application/json');

try {
    // Load Laravel
    require_once __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    $response = [
        'status' => 'checking',
        'timestamp' => date('Y-m-d H:i:s'),
        'tests' => []
    ];
    
    // Test 1: Check if DB connection works
    try {
        $pdo = DB::connection()->getPdo();
        $response['tests']['connection'] = [
            'status' => 'success',
            'message' => 'Database connection successful',
            'driver' => DB::connection()->getDriverName()
        ];
    } catch (Exception $e) {
        $response['tests']['connection'] = [
            'status' => 'failed',
            'message' => $e->getMessage()
        ];
        $response['status'] = 'failed';
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    // Test 2: Check tables exist
    try {
        $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
        $tableNames = array_map(fn($t) => $t->table_name, $tables);
        
        $response['tests']['tables'] = [
            'status' => 'success',
            'count' => count($tableNames),
            'tables' => $tableNames
        ];
    } catch (Exception $e) {
        $response['tests']['tables'] = [
            'status' => 'failed',
            'message' => $e->getMessage()
        ];
    }
    
    // Test 3: Check migrations table
    try {
        $migrations = DB::table('migrations')->count();
        $response['tests']['migrations'] = [
            'status' => 'success',
            'count' => $migrations
        ];
    } catch (Exception $e) {
        $response['tests']['migrations'] = [
            'status' => 'failed',
            'message' => $e->getMessage()
        ];
    }
    
    // Test 4: Check users table
    try {
        $users = DB::table('users')->count();
        $response['tests']['users'] = [
            'status' => 'success',
            'count' => $users
        ];
    } catch (Exception $e) {
        $response['tests']['users'] = [
            'status' => 'failed',
            'message' => $e->getMessage()
        ];
    }
    
    // Test 5: Check database info
    try {
        $dbConfig = [
            'connection' => config('database.default'),
            'host' => config('database.connections.pgsql.host'),
            'port' => config('database.connections.pgsql.port'),
            'database' => config('database.connections.pgsql.database')
        ];
        $response['tests']['config'] = [
            'status' => 'success',
            'info' => $dbConfig
        ];
    } catch (Exception $e) {
        $response['tests']['config'] = [
            'status' => 'failed',
            'message' => $e->getMessage()
        ];
    }
    
    $response['status'] = 'success';
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
