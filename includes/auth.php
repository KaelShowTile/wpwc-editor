<?php

require_once __DIR__.'/../functions.php'; 

function wpe_check_auth() {

    // Check existing auth
    if (!empty($_SESSION['wpe_authenticated'])) {
        return true;
    }

    // Process login form
   if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wpe_login'])) 
   {    // Load config
        $config_file = __DIR__.'/../includes/config.php';
        if (!file_exists($config_file)) {
            $error = 'Config file missing';
            wpe_log($error);
            return ['error' => $error];
        }

        $config = require $config_file;

        // Verify WordPress path
        $wp_load_path = $config['wordpress']['path'].'/wp-load.php';
        if (!file_exists($wp_load_path)) {
            $error = 'WordPress not found at: '.$wp_load_path;
            wpe_log($error);
            return ['error' => 'System configuration error'];
        }

        require_once $wp_load_path;

        // Authenticate
        $user = get_user_by('login', $_POST['username']);
        if (!$user) {
            $error = 'User not found: '.$_POST['username'];
            wpe_log($error);
            return ['error' => 'Invalid credentials'];
        }

        if (!wp_check_password($_POST['password'], $user->user_pass, $user->ID)) {
            $error = 'Invalid password for: '.$user->user_login;
            wpe_log($error);
            return ['error' => 'Invalid credentials'];
        }

        if (!in_array('edit_posts', $user->allcaps)) {
            $error = 'Insufficient permissions: '.$user->user_login;
            wpe_log($error);
            return ['error' => 'Admin/editor access required'];
        }

        // Successful login
        $_SESSION['wpe_authenticated'] = true;
        $_SESSION['wpe_user'] = [
            'id' => $user->ID,
            'name' => $user->display_name
        ];

        return true;
    }

    return false;
}