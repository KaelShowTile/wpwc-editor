<?php
require_once __DIR__.'/../functions.php';
require_once __DIR__.'/session_manager.php';
wpe_start_session();

// Load configuration
$config = require __DIR__.'/config.php';


// Initialize response array
$response = ['success' => false, 'message' => '', 'media' => []];

try {
    // Load WordPress environment
    $wp_path = $config['wordpress']['path'];
    if (!file_exists($wp_path . '/wp-load.php')) {
        throw new Exception('WordPress path is incorrect');
    }

    // Load WordPress
    define('WP_USE_THEMES', false);
    require_once $wp_path . '/wp-load.php';

    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = isset($_GET['per_page']) ? min(100, max(1, intval($_GET['per_page']))) : 24;
    $offset = ($page - 1) * $per_page;

    // Get media items (images only) with pagination
    $args = [
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'post_status' => 'inherit',
        'posts_per_page' => $per_page + 1, // Get one extra to check if there are more
        'offset' => $offset,
        'orderby' => 'date',
        'order' => 'DESC'
    ];
    
    $media_query = new WP_Query($args);
    $media_items = [];
    $has_more = false;
    
    // Check if there are more items than requested
    if ($media_query->have_posts()) {
        $count = 0;
        while ($media_query->have_posts() && $count < $per_page) {
            $media_query->the_post();
            $attachment_id = get_the_ID();
            
            $media_items[] = [
                'id' => $attachment_id,
                'title' => get_the_title(),
                'url' => wp_get_attachment_url($attachment_id),
                'thumbnail' => wp_get_attachment_thumb_url($attachment_id),
                'date' => get_the_date('Y-m-d H:i:s'),
                'date_formatted' => get_the_date('M j, Y')
            ];
            $count++;
        }
        
        // Check if there are more items
        $has_more = $media_query->have_posts();
    }
    
    wp_reset_postdata();
    
    $response['success'] = true;
    $response['media'] = $media_items;
    $response['has_more'] = $has_more;
    $response['page'] = $page;
    $response['total_loaded'] = count($media_items) + $offset;
    $response['message'] = 'Media items loaded successfully';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;