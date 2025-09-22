<?php
require_once __DIR__.'/../functions.php';
require_once __DIR__.'/session_manager.php';
wpe_start_session();

// Load configuration
$config = require __DIR__.'/config.php';

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // Check if it's an AJAX request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['action']) || $_POST['action'] !== 'upload_media') {
        throw new Exception('Invalid request');
    }

    // Check if file was uploaded
    if (empty($_FILES['media_file']) || $_FILES['media_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error');
    }

    // Load WordPress environment
    $wp_path = $config['wordpress']['path'];
    if (!file_exists($wp_path . '/wp-load.php')) {
        throw new Exception('WordPress path is incorrect');
    }

    // Load WordPress
    define('WP_USE_THEMES', false);
    require_once $wp_path . '/wp-load.php';

    // Check if WordPress loaded successfully
    if (!function_exists('wp_handle_upload')) {
        throw new Exception('WordPress functions not available');
    }

    // Handle file upload
    $uploaded_file = $_FILES['media_file'];
    $upload_overrides = ['test_form' => false];
    
    // Move uploaded file to WordPress uploads directory
    $movefile = wp_handle_upload($uploaded_file, $upload_overrides);
    
    if (isset($movefile['error'])) {
        throw new Exception($movefile['error']);
    }
    
    // Get additional image data
    $image_title = !empty($_POST['image_title']) ? sanitize_text_field($_POST['image_title']) : '';
    $image_alt = !empty($_POST['image_alt']) ? sanitize_text_field($_POST['image_alt']) : '';
    $image_description = !empty($_POST['image_description']) ? sanitize_textarea_field($_POST['image_description']) : '';
    
    // Use filename as title if not provided
    if (empty($image_title)) {
        $image_title = preg_replace('/\.[^.]+$/', '', basename($movefile['file']));
    }
    
    // Prepare attachment data
    $attachment = [
        'post_mime_type' => $movefile['type'],
        'post_title' => $image_title,
        'post_content' => $image_description,
        'post_status' => 'inherit'
    ];
    
    // Insert attachment
    $attach_id = wp_insert_attachment($attachment, $movefile['file']);
    
    if (is_wp_error($attach_id)) {
        throw new Exception($attach_id->get_error_message());
    }
    
    // Generate attachment metadata and create image sub-sizes
    require_once $wp_path . '/wp-admin/includes/image.php';
    $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
    wp_update_attachment_metadata($attach_id, $attach_data);
    
    // Update alt text if provided
    if (!empty($image_alt)) {
        update_post_meta($attach_id, '_wp_attachment_image_alt', $image_alt);
    }
    
    $response['success'] = true;
    $response['message'] = 'File uploaded successfully';
    $response['attachment_id'] = $attach_id;
    $response['url'] = wp_get_attachment_url($attach_id);

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;