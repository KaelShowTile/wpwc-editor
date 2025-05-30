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

//Error Log
function wpe_log($message) {
    $log_file = __DIR__.'/debug.log';
    $entry = '['.date('Y-m-d H:i:s').'] '.$message.PHP_EOL;
    
    file_put_contents($log_file, $entry, FILE_APPEND);

    echo '<!-- DEBUG: '.htmlspecialchars($message).' -->';
}