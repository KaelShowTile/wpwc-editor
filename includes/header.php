<?php 

// Enable error display temporarily
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start output buffering to prevent header errors
ob_start();

require_once __DIR__.'/../includes/session_manager.php';  
wpe_start_session();

try {
    require_once __DIR__.'/auth.php';
    
    $auth_result = wpe_check_auth();
    if ($auth_result !== true) {
        // Store errors in session
        if (is_array($auth_result)) {
            $_SESSION['login_error'] = $auth_result['error'];
            wpe_log('Auth error: ' . $auth_result['error']);
        }
        
        // Only redirect if not already on index.php
        if (basename($_SERVER['PHP_SELF']) !== 'index.php') {
            header('Location: index.php');
            exit;
        }
    }
    
    // Load WordPress only after successful auth
    if (!empty($_SESSION['wpe_authenticated'])) {
        $config = require __DIR__.'/../includes/config.php';
        if (!file_exists($config['wordpress']['path'].'/wp-load.php')) {
            throw new Exception('WordPress path is invalid');
        }
        require_once $config['wordpress']['path'].'/wp-load.php';
    }
} catch (Exception $e) {
    wpe_log('Header error: ' . $e->getMessage());
    die('System error occurred. Check logs.');
}

ob_end_flush();

require_once __DIR__.'/../functions.php';

$config = require __DIR__.'/../includes/config.php';
require_once $config['wordpress']['path'].'/wp-load.php';

$logo_url = get_site_logo_url();
if(!$logo_url){
    $logo_url = tool_url('/assets/img/logo.svg');
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>WPWC Editor - <?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo tool_url('/assets/css/style.css'); ?>" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&family=Work+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

    <header>

        <div class="container">
            <div class="row align-items-end">

                <div class="col">
                    <div class="logo-container">
                        <img src="<?php echo $logo_url; ?>">
                    </div>
                </div>

                <div class="col">
                    <nav>
                        <ul class="menu-list">
                            <li class="menu-item"> <a href="<?php echo tool_url('index.php'); ?>">Home</a></li>
                            <li class="menu-item"> <a href="<?php echo tool_url('product.php'); ?>">Products</a></li>
                            <li class="menu-item"> <a href="<?php echo tool_url('attribute.php'); ?>">Attribute</a></li>
                            <li class="menu-item"> <a href="<?php echo tool_url('setting.php'); ?>">Setting</a></li>
                        </ul>
                    </nav>
                </div>

            </div>
        </div>

    </header>