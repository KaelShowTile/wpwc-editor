<?php

require_once __DIR__.'/../functions.php';
require_once __DIR__.'/session_manager.php';
wpe_start_session();

// Load configuration
$config = require __DIR__.'/config.php';

// Initialize response array
$response = ['success' => false, 'message' => '', 'media' => []];

try {
    // Check if it's an AJAX request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['action']) || $_POST['action'] !== 'get_media_by_ids') {
        throw new Exception('Invalid request');
    }
    
    // Check if media IDs are provided
    if (empty($_POST['media_ids'])) {
        throw new Exception('Media IDs are required');
    }
    
    // Load WordPress environment
    $wp_path = $config['wordpress']['path'];
    if (!file_exists($wp_path . '/wp-load.php')) {
        throw new Exception('WordPress path is incorrect');
    }

    // Load WordPress
    define('WP_USE_THEMES', false);
    require_once $wp_path . '/wp-load.php';
    
    // Get media items by IDs
    $media_ids = is_array($_POST['media_ids']) ? $_POST['media_ids'] : explode(',', $_POST['media_ids']);
    $media_ids = array_map('intval', $media_ids);
    $media_items = [];
    
    foreach ($media_ids as $media_id) {
        $post = get_post($media_id);
        
        if ($post && $post->post_type === 'attachment') {
            $media_items[] = [
                'id' => $media_id,
                'title' => $post->post_title,
                'url' => wp_get_attachment_url($media_id),
                'thumbnail' => wp_get_attachment_thumb_url($media_id)
            ];
        }
    }
    
    $response['success'] = true;
    $response['media'] = $media_items;
    $response['message'] = 'Media items loaded successfully';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;