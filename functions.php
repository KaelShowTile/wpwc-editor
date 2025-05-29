<?php 

function tool_url($path = '') {
    static $base;
    if (!$base) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $base = "{$protocol}://{$_SERVER['HTTP_HOST']}" . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    }
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}