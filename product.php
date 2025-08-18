<?php 

$pageTitle = "Products";

require_once __DIR__.'/includes/header.php'; 
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/load_intergrations.php';

// Load WordPress environment
require_once $config['wordpress']['path'].'/wp-load.php';

// Database connection
global $wpdb;

// Connect to program database
$program_db = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['name']}",
    $config['db']['user'],
    $config['db']['password']
);
$program_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get active attribute taxonomies from settings table
$active_taxonomies = [];
try {
    $stmt = $program_db->query("
        SELECT setting_value 
        FROM {$config['db']['prefix']}settings 
        WHERE setting_name = 'attribute'
    ");
    $active_taxonomies = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log('Error fetching active taxonomies: ' . $e->getMessage());
}

// Get attribute taxonomy names
$taxonomy_names = [];
foreach ($active_taxonomies as $taxonomy) {
    $name = ucwords(str_replace(['pa_', '_'], ['', ' '], $taxonomy));
    $taxonomy_names[$taxonomy] = $name;
}

//load active plugin
$savedPlugins = get_actived_plugins($config['db']['host'], $config['db']['name'], $config['db']['prefix'], $config['db']['user'], $config['db']['password']);

$glint_product_quantity_active = false;
if(is_plugin_actived("glint-product-quantity", $savedPlugins)){
    $glint_product_quantity_active = true;
}

$glint_sample_product_active = false;
if(is_plugin_actived("glint-sample-product", $savedPlugins)){
    $glint_sample_product_active = true;
}

?>
<!-- CSS for product table -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<script>
    // Store attribute terms in localStorage
    const attributeTerms = <?= $attribute_terms_json ?>;
    localStorage.setItem('attributeTerms', JSON.stringify(attributeTerms));
    localStorage.setItem('attributeTermsTimestamp', Date.now());
    
    // Set expiration (1 hour)
    localStorage.setItem('attributeTermsExpiration', 3600000);
</script>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script type="text/javascript" src="<?php echo tool_url('/assets/js/products.js'); ?>" id="products-js"></script>
<script type="text/javascript" src="<?php echo tool_url('/assets/js/product-saving.js'); ?>" id="product-saving-js"></script>

<?php if($glint_product_quantity_active == true): ?>
<script type="text/javascript" src="<?php echo tool_url('/assets/js/glint-quantity-saving.js'); ?>" id="glint-quantity-saving-js"></script>
<?php endif; ?>

<div class="container">

    <div class="page-header-container">
        <h1>Products</h1>
        <p>Bulk edit WooCommerce products</p>
    </div>

</div>

<div class="container wpwc-list-container">

    <div class="row mb-4 wpwc-intro-container">
        <div class="col-md-3">
            <div class="card stats-card">
                <i class="fas fa-box fa-2x text-primary"></i>
                <div class="stats-number">
                    <?php 
                    $product_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'");
                    echo number_format($product_count);
                    ?>
                </div>
                <div class="stats-label">Products</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card">
                <i class="fas fa-tags fa-2x text-success"></i>
                <div class="stats-number">
                    <?php 
                    $category_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'product_cat'");
                    echo number_format($category_count);
                    ?>
                </div>
                <div class="stats-label">Categories</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card">
                <i class="fas fa-layer-group fa-2x text-info"></i>
                <div class="stats-number">
                    <?php 
                    $variation_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product_variation'");
                    echo number_format($variation_count);
                    ?>
                </div>
                <div class="stats-label">Variations</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card">
                <i class="fas fa-database fa-2x text-warning"></i>
                <div class="stats-number">
                    <?php 
                    $db_size = $wpdb->get_var("
                        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) 
                        FROM information_schema.TABLES 
                        WHERE table_schema = '".DB_NAME."'
                    ");
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
            <div class="d-flex btns-align-right">
                <button id="bulkEditBtn" class="btn btn-primary btn-action">
                    <i class="fas fa-edit me-1"></i> Bulk Edit
                </button>
                <button class="btn btn-success btn-action">
                    <i class="fas fa-plus me-1"></i> Add Product
                </button>
                <button class="btn btn-outline-secondary btn-action" id="refreshBtn">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>
        </div>
    </div>

</div>

<!-- Bulk Edit Panel -->
<div class="bulk-edit-panel hide-this-area" id="bulkEditPanel">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Bulk Edit</h5>
            <div class="d-flex">
                <div class="me-3">
                    <span id="selectedCount">0</span> products selected
                </div>
                <button class="btn btn-sm btn-outline-secondary me-2" id="cancelBulkEdit">
                    Cancel
                </button>
                <button class="btn btn-sm btn-primary" id="applyBulkEdit">
                    Apply Changes
                </button>
            </div>
        </div>
        <hr class="mt-2 mb-3">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Price</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" step="0.01" class="form-control" id="bulkPrice" placeholder="Regular price">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Sale Price</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" step="0.01" class="form-control" id="bulkSalePrice" placeholder="Sale price">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Stock Status</label>
                <select class="form-select" id="bulkStockStatus">
                    <option value="">-- No Change --</option>
                    <option value="instock">In Stock</option>
                    <option value="outofstock">Out of Stock</option>
                    <option value="onbackorder">On Backorder</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Actions</label>
                <div>
                    <button class="btn btn-sm btn-outline-danger" id="bulkDelete">
                        <i class="fas fa-trash me-1"></i> Delete Selected
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container column-control-container">
    <div class="row">
        <p>Show/hide columns</p>
        <ul>
            <li><input class="column-check-input" type="checkbox" value="show" id="hide-price-col" checked=""><span>Pirce</span></li>
            <li><input class="column-check-input" type="checkbox" value="show" id="hide-stock-col" checked=""><span>Stock</span></li>
            <li><input class="column-check-input" type="checkbox" value="show" id="hide-description-col" checked=""><span>Description</span></li>
            <li><input class="column-check-input" type="checkbox" value="show" id="hide-shipping-col" checked=""><span>Shipping</span></li>
            <li><input class="column-check-input" type="checkbox" value="show" id="hide-attribute-col" checked=""><span>Attribute</span></li>
            <?php if($glint_product_quantity_active == true): ?>
            <li><input class="column-check-input" type="checkbox" value="show" id="hide-quantity-col" checked=""><span>Quantity</span></li>
            <?php endif; ?>
            <?php if($glint_sample_product_active == true): ?>
            <li><input class="column-check-input" type="checkbox" value="show" id="hide-sample-product" checked=""><span>Sample Product</span></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<div class="container wpwc-list-container">
    <!-- Products Table -->
    <div class="card">
        <div class="card-body p-0">
            <table id="productsTable" class="table table-hover" style="width:100%">
                <thead class="fixed-header">
                    <tr>
                        <th width="50">ID</th>
                        <th width="80" id="thumb-col">Image</th>
                        <th id="title-col">Product Name</th>
                        <th width="120" class="hide-this-area">SKU</th>
                        <th width="100" class="price-col">Price</th>
                        <th width="100" class="price-col">Sale Price</th>
                        <th width="120" class="stock-col">Stock</th>
                        <th width="120" class="description-col">Description</th>
                        <th width="80" class="shipping-col">Weight</th>
                        <th width="80" class="shipping-col">Length</th>
                        <th width="80" class="shipping-col">Width</th>
                        <th width="80" class="shipping-col">Height</th>
                        <th width="80" class="shipping-col">Unit/Pallet</th>
                        <?php foreach ($active_taxonomies as $taxonomy): ?>
                        <th width="150" class="attribute-col" data-taxonomy="<?= esc_attr($taxonomy) ?>">
                        <?= esc_html($taxonomy_names[$taxonomy]) ?>
                        </th>
                        <?php endforeach; ?>
                        <th width="80" id="status-col">Status</th>
                        <?php if($glint_product_quantity_active == true):?>
                        <th width="100" class="quantity-col">Step</th>
                        <th width="100" class="quantity-col">Suffix</th>
                        <?php endif; ?>
                        <th width="100">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $product_load_string = "
                        SELECT 
                            p.ID, 
                            p.post_title, 
                            p.post_content,
                            MAX(CASE WHEN pm1.meta_key = '_sku' THEN pm1.meta_value END) AS sku,
                            MAX(CASE WHEN pm1.meta_key = '_regular_price' THEN pm1.meta_value END) AS regular_price,
                            MAX(CASE WHEN pm1.meta_key = '_sale_price' THEN pm1.meta_value END) AS sale_price,
                            MAX(CASE WHEN pm1.meta_key = '_stock_status' THEN pm1.meta_value END) AS stock_status,
                            MAX(CASE WHEN pm1.meta_key = '_weight' THEN pm1.meta_value END) AS weight,
                            MAX(CASE WHEN pm1.meta_key = '_length' THEN pm1.meta_value END) AS length,
                            MAX(CASE WHEN pm1.meta_key = '_width' THEN pm1.meta_value END) AS width,
                            MAX(CASE WHEN pm1.meta_key = '_height' THEN pm1.meta_value END) AS height,
                            MAX(CASE WHEN pm1.meta_key = 'unitperpallet' THEN pm1.meta_value END) AS pallet,
                            MAX(CASE WHEN pm1.meta_key = '_thumbnail_id' THEN pm1.meta_value END) AS thumbnail_id";
                    
                    if($glint_product_quantity_active == true){
                        $product_load_string .=",
                            g.glint_qty_suffix,
                            g.glint_qty_step";
                    }

                    $product_load_string .=" 
                        FROM {$wpdb->posts} p
                        LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id";

                    if($glint_product_quantity_active == true){
                        $product_load_string .="
                        LEFT JOIN {$wpdb->prefix}glint_product_qty g ON p.ID = g.post_id";
                    }

                    $product_load_string .="
                        WHERE p.post_type = 'product'
                        GROUP BY p.ID, g.meta_id";

                    $products = $wpdb->get_results($product_load_string);

                    $product_attributes = [];
                    foreach ($products as $product) {
                        // Get attribute values for each product
                        foreach ($active_taxonomies as $taxonomy) {
                            $terms = wp_get_post_terms($product->ID, $taxonomy, ['fields' => 'names']);
                            if(is_array($terms)){
                                $product_attributes[$product->ID][$taxonomy] = implode(', ', $terms);
                            }
                        }
                    }

                    // Get all attribute terms from WordPress
                    $all_attribute_terms = [];
                    foreach ($active_taxonomies as $taxonomy) {
                        $terms = get_terms([
                            'taxonomy' => $taxonomy,
                            'hide_empty' => false,
                            'fields' => 'id=>name'
                        ]);
                        
                        $all_attribute_terms[$taxonomy] = $terms;
                    }

                    // Convert to JSON for JavaScript
                    $attribute_terms_json = json_encode($all_attribute_terms);
                    
                    foreach ($products as $product) :
                        $thumbnail_url = '';
                        
                        if ($product->thumbnail_id) {
                            $thumbnail_url = wp_get_attachment_thumb_url($product->thumbnail_id);
                        }
                        
                        if (!$thumbnail_url) {
                            $thumbnail_url = 'https://placehold.co/80x80';
                        }

                        $is_sample_product = false;

                        if($glint_product_quantity_active == true){
                            $prefix = "Sample of";
                            if (strncmp($product->post_title, $prefix, strlen($prefix)) === 0) {
                                 $is_sample_product = true;
                            }
                        }
                        
                    ?>

                    <?php if($is_sample_product == false): ?>
                    <tr data-id="<?= $product->ID ?>" >
                    <?php else: ?>
                    <tr data-id="<?= $product->ID ?>" class="sample-product-row">
                    <?php endif; ?>

                        <!-- Product ID -->
                        <td class="editable-cell" data-field="id"><?= $product->ID ?></td>

                        <!-- Product Thunbnail -->
                        <td><img src="<?= $thumbnail_url ?>" alt="Thumbnail" class="product-thumb"></td>

                        <!-- Product Title -->
                        <td class="editable-cell" contenteditable="true" data-field="post_title" data-productid="<?= $product->ID ?>" data-original="<?= esc_attr($product->post_title) ?>"><?= esc_html($product->post_title) ?></td>

                        <!-- SKU -->
                        <td class="editable-cell hide-this-area" data-field="_sku" contenteditable="true" data-productid="<?= $product->ID ?>" data-original="<?= esc_attr($product->sku) ?>"><?= esc_html($product->sku) ?></td>

                        <!-- Price -->
                        <td class="price-col">
                            <div class="editable-field price-field" 
                                contenteditable="true"
                                data-field="_regular_price"
                                data-productid="<?= $product->ID ?>"
                                data-original="<?= esc_attr($product->regular_price) ?>">
                                <?= $product->regular_price ? wc_price($product->regular_price) : '&mdash;' ?>
                            </div>
                        </td>
                        
                        <!-- Sales Price -->
                        <td class="price-col">
                            <div class="editable-field price-field" 
                                contenteditable="true"
                                data-field="_sale_price"
                                data-productid="<?= $product->ID ?>"
                                data-original="<?= esc_attr($product->sale_price) ?>">
                                <?= $product->sale_price ? wc_price($product->sale_price) : '&mdash;' ?>
                            </div>
                        </td>
                        
                        <!-- Stock Status -->
                        <td class="editable-cel stock-col" data-field="stock_status" data-productid="<?= $product->ID ?>">
                            <select class="form-select stock-status-select" id="bulkStockStatus" data-productid="<?= $product->ID ?>" data-original="<?= esc_attr($product->stock_status) ?>">
                                <option value="instock" <?= strtolower($product->stock_status) === 'instock' ? 'selected' : '' ?>>instock</option>
                                <option value="outofstock" <?= strtolower($product->stock_status) === 'outofstock' ? 'selected' : '' ?>>outofstock</option>
                                <option value="onbackorder" <?= strtolower($product->stock_status) === 'onbackorder' ? 'selected' : '' ?>>onbackorder</option>
                            </select> 
                        </td>

                        <!-- Product Content -->
                        <td class="edit-product-content description-col">
                            <!-- button -->
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#edit-content-<?= $product->ID ?>" data-id="<?= $product->ID ?>" data-content="<?= esc_attr($product->post_content) ?>">Edit</button>
                            <!-- modal -->
                            <div class="modal fade" id="edit-content-<?= $product->ID ?>" tabindex="-1" role="dialog" aria-labelledby="ContentLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="ContentLabel"><?= esc_html($product->post_title) ?></h5>
                                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <textarea data-id="<?= $product->ID ?>"><?= htmlspecialchars($product->post_content, ENT_QUOTES) ?></textarea>
                                        
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary save-description" data-bs-dismiss="modal" data-id="<?= $product->ID ?>">Save</button>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Weight -->
                        <td class="editable-cell shipping-col" contenteditable="true" data-field="_weight" data-productid="<?= $product->ID ?>" data-original="<?= esc_attr($product->weight) ?>"><?= esc_html($product->weight) ?></td>

                        <!-- length -->
                        <td class="editable-cell shipping-col" contenteditable="true" data-field="_length" data-productid="<?= $product->ID ?>" data-original="<?= esc_attr($product->length) ?>"><?= esc_html($product->length) ?></td>

                        <!-- width -->
                        <td class="editable-cell shipping-col" contenteditable="true" data-field="_width" data-productid="<?= $product->ID ?>" data-original="<?= esc_attr($product->width) ?>"><?= esc_html($product->width) ?></td>

                        <!-- height -->
                        <td class="editable-cell shipping-col" contenteditable="true" data-field="_height" data-productid="<?= $product->ID ?>" data-original="<?= esc_attr($product->height) ?>"><?= esc_html($product->height) ?></td>

                        <!-- Unit Per Pallet -->
                        <td class="editable-cell shipping-col" contenteditable="true" data-field="unitperpallet" data-productid="<?= $product->ID ?>" data-original="<?= esc_attr($product->pallet) ?>"><?= esc_html($product->pallet) ?></td>

                        <!-- Attribute -->
                        <?php foreach ($active_taxonomies as $taxonomy): 
                            $attr_value = $product_attributes[$product->ID][$taxonomy] ?? '';
                        ?>
                        <td class="editable-cell attribute-cell attribute-col" 
                            data-field="attribute" 
                            data-taxonomy="<?= esc_attr($taxonomy) ?>"
                            data-productid="<?= $product->ID ?>">
                            <div class="attribute-value">
                                <?php if (!empty($attr_value)): ?>
                                    <?= esc_html($attr_value) ?>
                                <?php else: ?>
                                    <span class="text-muted"></span>
                                <?php endif; ?>
                            </div>
                            <div class="attribute-input">
                                <input type="text" class="form-control attribute-autocomplete" 
                                    value="<?= esc_attr($attr_value) ?>"
                                    data-taxonomy="<?= esc_attr($taxonomy) ?>"
                                    placeholder="">
                                <div class="autocomplete-results"></div>
                            </div>
                        </td>
                        <?php endforeach; ?>

                        <!-- Product Status -->
                        <td>
                            <span class="badge bg-success">Published</span>
                        </td>

                        <!-- Glint Product Quantity -->
                        <?php if($glint_product_quantity_active == true):?>

                        <!-- Step -->
                        <td class="quantity-col">
                            <div class="editable-field glint-product-step" 
                                contenteditable="true"
                                data-field="_quantity_step"
                                data-productid="<?= $product->ID ?>"
                                data-original="<?= esc_attr($product->glint_qty_step) ?>">
                                <?= esc_html(($product->glint_qty_step)) ?>         
                            </div>
                        </td>

                        <!-- Suffix -->
                        <td class="editable-cel quantity-col" data-field="glint_qty_suffix" data-productid="<?= $product->ID ?>">
                            <select class="form-select glint-suffix-select" id="bulkSuffixStatus" data-productid="<?= $product->ID ?>" data-original="<?= esc_attr($product->glint_qty_suffix) ?>">
                                <option value="m2" <?= strtolower($product->glint_qty_suffix) === 'm2' ? 'selected' : '' ?>>m2</option>
                                <option value="sheet" <?= strtolower($product->glint_qty_suffix) === 'sheet' ? 'selected' : '' ?>>sheet</option>
                                <option value="ea" <?= strtolower($product->glint_qty_suffix) === 'ea' ? 'selected' : '' ?>>ea</option>
                                <option value="lm" <?= strtolower($product->glint_qty_suffix) === 'lm' ? 'selected' : '' ?>>lm</option>
                                <option value="set" <?= strtolower($product->glint_qty_suffix) === 'set' ? 'selected' : '' ?>>set</option>
                                <option value="bag" <?= strtolower($product->glint_qty_suffix) === 'bag' ? 'selected' : '' ?>>bag</option>
                                <option value="" <?= strtolower($product->glint_qty_suffix) === '' ? 'selected' : '' ?>>none</option>
                            </select> 
                        </td>
                        <?php endif; ?>

                        <!-- Buttons -->
                        <td>
                            <a href= "<?= $config['wordpress']['url'] ?>wp-admin/post.php?post=<?= $product->ID ?>&action=edit" class="btn btn-sm btn-outline-primary edit-btn" target="_blank">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-danger delete-btn" data-id="<?= $product->ID ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__.'/includes/footer.php';