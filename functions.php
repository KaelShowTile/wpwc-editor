<?php 

//Get Path
function tool_url($path = '') {
    static $base;
    if (!$base) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $base = "{$protocol}://{$_SERVER['HTTP_HOST']}" . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    }
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

//Site URL
function getRootUrl() {
    // Detect protocol (http vs https)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
        || ($_SERVER['SERVER_PORT'] == 443) 
        ? 'https://' 
        : 'http://';

    // Get domain/host
    $host = $_SERVER['HTTP_HOST'];

    // Get path to document root
    $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
    
    // Get absolute path to root index.php
    $rootIndexPath = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] . '/index.php'));
    
    // Verify index.php exists in root
    if (!file_exists($rootIndexPath)) {
        throw new Exception("index.php not found in document root");
    }

    return $protocol . $host;
}

// Usage:
try {
    $rootIndexUrl = getRootUrl();
    echo $rootIndexUrl; // Output: https://example.com/index.php
} catch (Exception $e) {
    die($e->getMessage());
}

//Error Log
function wpe_log($message) {
    $log_file = __DIR__.'/debug.log';
    $entry = '['.date('Y-m-d H:i:s').'] '.$message.PHP_EOL;
    
    file_put_contents($log_file, $entry, FILE_APPEND);

    echo '<!-- DEBUG: '.htmlspecialchars($message).' -->';
}