<?php

/**
 * Vercel Serverless Entrypoint for SISQA (Laravel + Vue 3 SPA + REST API)
 */

// Configure writable storage directory in /tmp when running on Vercel Serverless
if (isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL']) || getenv('VERCEL')) {
    $tmpStorage = '/tmp/storage';
    $dirs = [
        $tmpStorage . '/app/public',
        $tmpStorage . '/framework/cache/data',
        $tmpStorage . '/framework/sessions',
        $tmpStorage . '/framework/testing',
        $tmpStorage . '/framework/views',
        $tmpStorage . '/logs',
    ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
    }

    // Default to zero-config SQLite in /tmp if no external Cloud MySQL host is configured yet
    if (!getenv('DB_HOST') && (!getenv('DB_CONNECTION') || getenv('DB_CONNECTION') === 'sqlite')) {
        $sqlitePath = '/tmp/database.sqlite';
        $isNewDb = !file_exists($sqlitePath);
        if ($isNewDb) {
            @touch($sqlitePath);
        }
        putenv('DB_CONNECTION=sqlite');
        putenv("DB_DATABASE={$sqlitePath}");
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $sqlitePath;
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = $sqlitePath;
    }
}

require __DIR__ . '/../public/index.php';

