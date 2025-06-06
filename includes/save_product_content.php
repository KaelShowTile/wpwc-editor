<?php
// save_product_stock.php
require_once __DIR__.'/../functions.php';
require_once __DIR__.'/session_manager.php';
wpe_start_session();

$config = require __DIR__.'/config.php';

// Include WordPress configuration
$wpConfigPath = $config['wordpress']['path'] . '/wp-config.php';
if (file_exists($wpConfigPath)) {
    include($wpConfigPath);
} else {
    die('WordPress config file not found.');
}

function sanitize_content($content) {
    // Allow specific tags only
    return strip_tags($content, '<p><strong><em><a><b><br><span><img><h1><h2><h3><h4><h5><h6><div>');
}

require_once $config['wordpress']['path'].'/wp-load.php';

// Database connection details from wp-config.php
$host = DB_HOST;
$dbname = DB_NAME;
$user = DB_USER;
$password = DB_PASSWORD;
$tablePrefix = $table_prefix;

try {
    // Create a PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if input is set
    if (isset($_POST['product_id']) && isset($_POST['content'])) {
        $productId = intval($_POST['product_id']);
        $newContent = sanitize_content($_POST['content']); 

        // Update the product description using the correct table prefix
        $stmt = $pdo->prepare("UPDATE {$tablePrefix}posts SET post_content = :content WHERE ID = :id");
        $stmt->bindParam(':content', $newContent);
        $stmt->bindParam(':id', $productId);

        if ($stmt->execute()) {
            // Success response
            echo json_encode(array('status' => 'success', 'message' => 'Description updated successfully!'));
        } else {
            // Error response
            echo json_encode(array('status' => 'error', 'message' => 'Failed to update the product description.'));
        }
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid input.'));
    }
} catch (PDOException $e) {
    // Handle connection error
    echo json_encode(array('status' => 'error', 'message' => 'Database error: ' . $e->getMessage()));
}
?>