<?php 

require_once __DIR__.'/../functions.php';
require_once __DIR__.'/session_manager.php';
wpe_start_session();

function get_actived_plugins($db_host, $db_name, $db_prefix, $user, $password)
{
    $getplugins = [];

    try {
        // Create PDO connection with proper error handling
        $pdo = new PDO(
            "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", 
            $user, 
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        
        $table = $db_prefix . 'settings';
        
        // Use prepared statement with parameter binding
        $stmt = $pdo->prepare("
            SELECT setting_value 
            FROM `$table` 
            WHERE setting_name = :setting_name
        ");
        
        // Execute with bound parameter
        $stmt->execute([':setting_name' => 'plugin']);
        
        // Fetch results
        $getplugins = $stmt->fetchAll(PDO::FETCH_COLUMN, 0); // Get first column as array
        
    } catch (PDOException $e) {
        error_log('PDO Error: ' . $e->getMessage());
    } catch (Exception $e) {
        error_log('General Error: ' . $e->getMessage());
    } finally {
        // Clean up resources
        $stmt = null;
        $pdo = null;
    }

    return $getplugins;
}

function is_plugin_actived($value, $savedPlugins) {
    foreach ($savedPlugins as $Plugins) {
        if (htmlspecialchars($Plugins) == $value) {
            return true;
        }
    }
    return false;
}