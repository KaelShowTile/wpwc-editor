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

//Error Log
function wpe_log($message) {
    $log_file = __DIR__.'/debug.log';
    $entry = '['.date('Y-m-d H:i:s').'] '.$message.PHP_EOL;
    
    file_put_contents($log_file, $entry, FILE_APPEND);

    echo '<!-- DEBUG: '.htmlspecialchars($message).' -->';
}

function get_site_logo_url(){
    static $logo_url = null;

    if ($logo_url !== null) {
        return $logo_url;
    }

    try {
        // Load configuration
        $config = require __DIR__.'/includes/config.php';

        // Connect to database
        $db = new PDO(
            "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4",
            $config['db']['user'],
            $config['db']['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );

        // Query for logo URL setting
        $stmt = $db->prepare("
            SELECT setting_value
            FROM `{$config['db']['prefix']}settings`
            WHERE setting_name = :setting_name
            LIMIT 1
        ");

        $stmt->execute([':setting_name' => 'log-url']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $logo_url = $result ? $result['setting_value'] : '';

    } catch (Exception $e) {
        wpe_log('Error getting site logo URL: ' . $e->getMessage());
        $logo_url = '';
    }

    return $logo_url;
}