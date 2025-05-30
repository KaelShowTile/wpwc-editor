<?php

function wpe_start_session() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start([
            'name' => 'wpe_session',
            'cookie_lifetime' => 0,
            'cookie_secure' => isset($_SERVER['HTTPS']),
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict'
        ]);
    }
    
    // Regenerate ID periodically for security
    if (!isset($_SESSION['wpe_created'])) {
        $_SESSION['wpe_created'] = time();
    } elseif (time() - $_SESSION['wpe_created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['wpe_created'] = time();
    }
}