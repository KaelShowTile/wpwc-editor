<?php
// refresh_attribute_terms.php
require_once __DIR__.'/../functions.php';
require_once __DIR__.'/session_manager.php';
wpe_start_session();

// Load configuration
$config = require __DIR__.'/config.php';

// Load WordPress environment
require_once $config['wordpress']['path'].'/wp-load.php';

// Get active attribute taxonomies from settings
$program_db = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['name']}",
    $config['db']['user'],
    $config['db']['password']
);
$program_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$active_taxonomies = [];
$stmt = $program_db->query("
    SELECT setting_value 
    FROM settings 
    WHERE setting_name = 'attribute'
");
$active_taxonomies = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get all attribute terms
$all_attribute_terms = [];
foreach ($active_taxonomies as $taxonomy) {
    $terms = get_terms([
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'fields' => 'id=>name'
    ]);
    
    $all_attribute_terms[$taxonomy] = $terms;
}

header('Content-Type: application/json');
echo json_encode($all_attribute_terms);