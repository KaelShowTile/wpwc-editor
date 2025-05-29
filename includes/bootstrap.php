<?php

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // Removes '/install' if currently there
$base_url = "{$protocol}://{$host}{$path}";

// Check if config exists
if (!file_exists(__DIR__.'/config.php')) {
    header("Location: {$base_url}/install/");
    exit;
}

// Verify config validity
$config = require __DIR__.'/config.php';

// Check DB connection
try {
    $db = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['name']}",
        $config['db']['user'],
        $config['db']['password']
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Verify WordPress path
if (!file_exists($config['wordpress']['path'].'/wp-load.php')) {
    die("Invalid WordPress path: {$config['wordpress']['path']}");
}

// Load WordPress environment (for later use)
require_once $config['wordpress']['path'].'/wp-load.php';