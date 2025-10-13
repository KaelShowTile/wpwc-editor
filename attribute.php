<?php 

$pageTitle = "Attributes";

require_once __DIR__.'/includes/header.php'; 
require_once __DIR__.'/includes/config.php';
require_once $config['wordpress']['path'].'/wp-load.php';

function get_actived_attributes_taxonomy($db_host, $db_name, $db_prefix, $user, $password)
{
    $getAttributes = [];

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
        $stmt->execute([':setting_name' => 'attribute']);
        
        // Fetch results
        $getAttributes = $stmt->fetchAll(PDO::FETCH_COLUMN, 0); // Get first column as array
        
    } catch (PDOException $e) {
        wpe_log('PDO Error: ' . $e->getMessage());
    } catch (Exception $e) {
        wpe_log('General Error: ' . $e->getMessage());
    } finally {
        // Clean up resources
        $stmt = null;
        $pdo = null;
    }

    return $getAttributes;
}

$savedTaxonomies = get_actived_attributes_taxonomy($config['db']['host'], $config['db']['name'], $config['db']['prefix'], $config['db']['user'], $config['db']['password']);

function get_saved_product_attributes($db_host, $db_name, $db_prefix, $user, $password)
{
    $getAttributes = [];

    try {
        // Create PDO connection with proper error handling
        $pdo = new PDO(
            "mysql:host=$db_host;dbname=$db_name", 
            $user, 
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        // Prepare and execute query
        $stmt = $pdo->query("SELECT attribute_id FROM " . $db_prefix . "attributes");
        
        // Fetch results
        $getAttributes = $stmt->fetchAll();
        
    } catch (PDOException $e) {
        wpe_log('PDO Error: ' . $e->getMessage());
    } catch (Exception $e) {
        wpe_log('General Error: ' . $e->getMessage());
    } finally {
        // Clean up resources
        $stmt = null;
        $pdo = null;
    }

    return $getAttributes;
}

$savedAttributes = get_saved_product_attributes($config['db']['host'], $config['db']['name'], $config['db']['prefix'], $config['db']['user'], $config['db']['password']);


// Connect to wp database
$db_host = DB_HOST;
$db_user = DB_USER;
$db_pass = DB_PASSWORD;
$db_name = DB_NAME;
$db_prefix = $wpdb->prefix;

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function get_product_attributes($db, $db_prefix) {
    $attributes = [];
    
    // Get attribute taxonomies
    $taxonomies = $db->query("
        SELECT DISTINCT taxonomy 
        FROM {$db_prefix}term_taxonomy 
        WHERE taxonomy LIKE 'pa_%'
    ")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($taxonomies as $taxonomy) {
        // Clean taxonomy name for display
        $display_name = ucwords(str_replace(['pa_', '_'], ['', ' '], $taxonomy));
        
        // Get terms for this taxonomy
        $terms = $db->query("
            SELECT t.term_id, t.name, t.slug, tt.count
            FROM {$db_prefix}terms t
            JOIN {$db_prefix}term_taxonomy tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy = '$taxonomy'
            ORDER BY t.name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($terms)) {
            $attributes[] = [
                'taxonomy' => $taxonomy,
                'name' => $display_name,
                'terms' => $terms
            ];
        }
    }
    
    return $attributes;
}

// Get attributes from database
$attributes = get_product_attributes($db, $db_prefix);

//check attribute ID
function is_attribute_exists($id, $savedAttributes) {
    foreach ($savedAttributes as $attribute) {
        if ($attribute['attribute_id'] == $id) {
            return true;
        }
    }
    return false;
}

//check attribute ID
function is_taxonomy_exists($slug, $savedTaxonomies) {
    foreach ($savedTaxonomies as $taxonomy) {
        if (htmlspecialchars($taxonomy) == $slug) {
            return true;
        }
    }
    return false;
}

?>


<div class="container">

    <div class="page-header-container">
        <h1>Attributes</h1>
        <p>Enable attribute on product editing</p>
    </div>

</div>

<div class="container wpwc-list-container">
        <!-- Stats Section -->
        <div class="row mb-4 wpwc-intro-container">
            <div class="col-md-3">
                <div class="card stats-card">
                    <i class="fas fa-tags fa-2x text-primary"></i>
                    <div class="stats-number"><?= count($attributes) ?></div>
                    <div class="stats-label">Attributes</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <i class="fas fa-list fa-2x text-success"></i>
                    <div class="stats-number">
                        <?php 
                        $total_terms = 0;
                        foreach ($attributes as $attr) {
                            $total_terms += count($attr['terms']);
                        }
                        echo $total_terms;
                        ?>
                    </div>
                    <div class="stats-label">Items</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <i class="fas fa-boxes fa-2x text-info"></i>
                    <div class="stats-number">
                        <?php 
                        $total_products = $db->query("SELECT COUNT(*) FROM {$db_prefix}posts WHERE post_type = 'product'")->fetchColumn();
                        echo $total_products;
                        ?>
                    </div>
                    <div class="stats-label">Products</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <i class="fas fa-database fa-2x text-warning"></i>
                    <div class="stats-number">
                        <?php 
                        $db_size = $db->query("
                            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) 
                            FROM information_schema.TABLES 
                            WHERE table_schema = '$db_name'
                        ")->fetchColumn();
                        echo $db_size ? $db_size.' MB' : 'N/A';
                        ?>
                    </div>
                    <div class="stats-label">Database</div>
                </div>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="action-bar">
            <div class="d-flex justify-content-between align-items-center">
                <div class="search-box flex-grow-1 me-3">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Search attributes or items...">
                </div>
                <div>
                    <button class="btn btn-outline-secondary btn-action">
                        <i class="fas fa-sync-alt me-1"></i> Refresh
                    </button>

                    <button class="btn btn-success btn-action">
                        <i class="fas fa-plus me-1"></i> Add New
                    </button>
                
                    <button id="saveAttributesBtn" class="btn btn-primary btn-action">
                        <i class="fas fa-save me-1"></i> Active Attribute
                    </button>
                    
                </div>
            </div>
        </div>

        <!-- Attributes Section -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">

                    <div class="card-body p-0">
                        <?php if (empty($attributes)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h4>No Attributes Found</h4>
                                <p class="text-muted">Your store doesn't have any product attributes yet.</p>
                                <button class="btn btn-primary mt-2">
                                    <i class="fas fa-plus me-1"></i> Create First Attribute
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="accordion" id="attributesAccordion">
                                <?php foreach ($attributes as $index => $attribute): ?>
                                    <div class="accordion-item border-0">
                                        <h2 class="accordion-header" id="heading<?= $index ?>">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                                    data-bs-target="#collapse<?= $index ?>" 
                                                    aria-expanded="true" aria-controls="collapse<?= $index ?>">
                                                <div class="form-check me-2">
                                                    <input class="attribute-check-input" type="checkbox" 
                                                        value="<?= $attribute['taxonomy'] ?>" id="attrCheck<?= $index ?>"
                                                        <?= is_taxonomy_exists($attribute['taxonomy'], $savedTaxonomies) ? 'checked' : '' ?>>
                                                </div>
                                                <strong><?= esc_html($attribute['name']) ?></strong>
                                                <span class="badge bg-secondary ms-2"><?= count($attribute['terms']) ?> items</span>
                                            </button>
                                        </h2>
                                        <div id="collapse<?= $index ?>" class="accordion-collapse collapse show" 
                                            aria-labelledby="heading<?= $index ?>" 
                                            data-bs-parent="#attributesAccordion">
                                            <div class="accordion-body p-0">
                                                <div class="list-group list-group-flush">
                                                    <?php foreach ($attribute['terms'] as $term): ?>
                                                        <div class="term-item">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" 
                                                                    value="<?= $term['term_id'] ?>" name="<?= $attribute['taxonomy'] ?>" 
                                                                    id="termCheck<?= $term['term_id'] ?>"
                                                                    <?= is_attribute_exists($term['term_id'], $savedAttributes) ? 'checked' : '' ?>>
                                                                <label class="form-check-label" for="termCheck<?= $term['term_id'] ?>">
                                                                    <?= esc_html($term['name']) ?>(<?= $term['count'] ?>)
                                                                    <small class="text-muted d-block">Slug: <?= $term['slug'] ?></small>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
</div>

<script type="text/javascript" src="<?php echo tool_url('/assets/js/attributes.js'); ?>" id="attribute-js"></script>

<?php require_once __DIR__.'/includes/footer.php';