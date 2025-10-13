<?php
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$base_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');

// Validate inputs
$required = ['db_host', 'db_name', 'db_user', 'wp_path', 'wp_url'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        die("Error: Missing required field '$field'");
    }
}

// Test database connection
try {
    $db = new PDO(
        "mysql:host={$_POST['db_host']}",
        $_POST['db_user'],
        $_POST['db_pass']
    );
    
    // Create database if not exists
    $db->exec("CREATE DATABASE IF NOT EXISTS `{$_POST['db_name']}`");
    $db->exec("USE `{$_POST['db_name']}`");
    $prefix = $_POST['db_prefix'] ?? 'wptool_';

    // Create setting table
    $db->exec("
         CREATE TABLE IF NOT EXISTS `{$prefix}settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `setting_name` VARCHAR(255) NOT NULL,
            `setting_value` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Create attribute table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `{$prefix}attributes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `attribute_id` VARCHAR(50) NOT NULL UNIQUE,
            `active_editing` TINYINT(1) DEFAULT 0,
            `attribute_cat` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Create history table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `{$prefix}update_history` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `product_id` INT(11) NOT NULL,
            `field_name` VARCHAR(255) NOT NULL,
            `old_value` TEXT,
            `new_value` TEXT NOT NULL,
            `user_identifier` VARCHAR(255),
            `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
            `request_data` JSON,
            `error_message` TEXT,
            PRIMARY KEY (`id`),
            INDEX `idx_product_field` (`product_id`, `field_name`),
            INDEX `idx_status_timestamp` (`status`, `timestamp`),
            INDEX `idx_timestamp` (`timestamp`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Verify WordPress path
if (!file_exists($_POST['wp_path'].'/wp-load.php')) {
    die("WordPress not found at specified path");
}

// Generate config file
$config = <<<EOT
<?php
// Auto-generated during install - DO NOT EDIT MANUALLY
return [
    'db' => [
        'host'      => '{$_POST['db_host']}',
        'name'      => '{$_POST['db_name']}',
        'user'      => '{$_POST['db_user']}',
        'password'  => '{$_POST['db_pass']}',
        'prefix'    => '{$prefix}'
    ],
    'wordpress' => [
        'path'      => '{$_POST['wp_path']}',
        'url'       => '{$_POST['wp_url']}'
    ]
];
EOT;

// Save config
if (!file_put_contents(__DIR__.'/../includes/config.php', $config)) {
    die("Failed to write config file");
}

// Set permissions
chmod(__DIR__.'/../includes/config.php', 0644);


// Redirect to main app
header("Location: {$base_url}/");