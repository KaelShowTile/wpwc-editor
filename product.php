<?php 

$pageTitle = "Products";

require_once __DIR__.'/includes/header.php'; 
require_once __DIR__.'/includes/config.php';

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



?>

<!-- CSS for product table -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

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
<div class="bulk-edit-panel" id="bulkEditPanel">
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
                        <th width="100">Price</th>
                        <th width="100">Sale Price</th>
                        <th width="120">Stock</th>
                        <?php foreach ($active_taxonomies as $taxonomy): ?>
                            <th width="150" data-taxonomy="<?= esc_attr($taxonomy) ?>">
                                <?= esc_html($taxonomy_names[$taxonomy]) ?>
                            </th>
                        <?php endforeach; ?>
                        <th width="80" id="status-col">Status</th>
                        <th width="100">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $products = $wpdb->get_results("
                        SELECT p.ID, p.post_title, 
                            MAX(CASE WHEN pm1.meta_key = '_sku' THEN pm1.meta_value END) AS sku,
                            MAX(CASE WHEN pm1.meta_key = '_regular_price' THEN pm1.meta_value END) AS regular_price,
                            MAX(CASE WHEN pm1.meta_key = '_sale_price' THEN pm1.meta_value END) AS sale_price,
                            MAX(CASE WHEN pm1.meta_key = '_stock_status' THEN pm1.meta_value END) AS stock_status,
                            MAX(CASE WHEN pm1.meta_key = '_thumbnail_id' THEN pm1.meta_value END) AS thumbnail_id
                        FROM {$wpdb->posts} p
                        LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id
                        WHERE p.post_type = 'product'
                        AND p.post_status = 'publish'
                        GROUP BY p.ID
                        ORDER BY p.ID DESC
                    ");

                    // Get attribute values for each product
                    $product_attributes = [];
                    foreach ($products as $product) {
                        foreach ($active_taxonomies as $taxonomy) {
                            $terms = wp_get_post_terms($product->ID, $taxonomy, ['fields' => 'names']);
                            $product_attributes[$product->ID][$taxonomy] = implode(', ', $terms);
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
                    ?>
                    <tr data-id="<?= $product->ID ?>">
                        <td class="editable-cell" data-field="id"><?= $product->ID ?></td>
                        <td>
                            <img src="<?= $thumbnail_url ?>" alt="Thumbnail" class="product-thumb">
                        </td>
                        <td class="editable-cell" data-field="title" contenteditable="true"><?= esc_html($product->post_title) ?></td>
                        <td class="editable-cell hide-this-area" data-field="sku" contenteditable="true"><?= esc_html($product->sku) ?></td>
                        <td class="editable-cell price-cell" data-field="regular_price" contenteditable="true">
                            <?= $product->regular_price ? wc_price($product->regular_price) : '&mdash;' ?>
                        </td>
                        <td class="editable-cell price-cell sale-price" data-field="sale_price" contenteditable="true">
                            <?= $product->sale_price ? wc_price($product->sale_price) : '&mdash;' ?>
                        </td>
                        <td class="editable-cell" data-field="stock_status">
                            <select class="form-select" id="bulkStockStatus">
                                <option value="instock" <?= strtolower($product->stock_status) === 'instock' ? 'selected' : '' ?>>instock</option>
                                <option value="outofstock" <?= strtolower($product->stock_status) === 'outofstock' ? 'selected' : '' ?>>outofstock</option>
                                <option value="onbackorder" <?= strtolower($product->stock_status) === 'onbackorder' ? 'selected' : '' ?>>onbackorder</option>
                            </select> 
                        </td>
                        <?php foreach ($active_taxonomies as $taxonomy): 
                            $attr_value = $product_attributes[$product->ID][$taxonomy] ?? '';
                        ?>
                        <td class="editable-cell attribute-cell" 
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
                        <td>
                            <span class="badge bg-success">Published</span>
                        </td>
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


<!-- JS for product table -->

<script>
    // Store attribute terms in localStorage
    const attributeTerms = <?= $attribute_terms_json ?>;
    localStorage.setItem('attributeTerms', JSON.stringify(attributeTerms));
    localStorage.setItem('attributeTermsTimestamp', Date.now());
    
    // Set expiration (1 hour)
    localStorage.setItem('attributeTermsExpiration', 3600000);
</script>

<script type="text/javascript" src="<?php echo tool_url('/assets/js/products.js'); ?>" id="products-js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize DataTable
        const table = $('#productsTable').DataTable({
            paging: true,
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            language: {
                search: "",
                searchPlaceholder: "Search products..."
            },
            columnDefs: [
                { targets: [0, 3, 4, 5, 6, 7, 8], orderable: true },
                { targets: [1, 2], orderable: false }
            ],
            initComplete: function() {
                $('.dataTables_filter input').addClass('form-control');
            }
        });
        
        // Row selection
        $('#productsTable tbody').on('click', 'tr', function(e) {
            if ($(e.target).is('button') || $(e.target).is('input')) {
                return;
            }
            
            $(this).toggleClass('selected');
            updateSelectedCount();
        });
        
        // Update selected count
        function updateSelectedCount() {
            const count = table.rows('.selected').count();
            $('#selectedCount').text(count);
            
            // Show/hide bulk edit panel
            if (count > 0) {
                $('#bulkEditPanel').addClass('active');
            } else {
                $('#bulkEditPanel').removeClass('active');
            }
        }
        
        // Toggle bulk edit panel
        $('#bulkEditBtn').click(function() {
            if ($('#bulkEditPanel').hasClass('active')) {
                $('#bulkEditPanel').removeClass('active');
                table.$('tr.selected').removeClass('selected');
                updateSelectedCount();
            } else {
                $('#bulkEditPanel').addClass('active');
            }
        });
        
        // Cancel bulk edit
        $('#cancelBulkEdit').click(function() {
            table.$('tr.selected').removeClass('selected');
            $('#bulkEditPanel').removeClass('active');
            updateSelectedCount();
        });
        
        // Inline editing
        $('#productsTable').on('focus', '[contenteditable]', function() {
            const $cell = $(this);
            $cell.data('original', $cell.text());
        }).on('blur', '[contenteditable]', function() {
            const $cell = $(this);
            const original = $cell.data('original');
            const newValue = $cell.text().trim();
            const field = $cell.data('field');
            const productId = $cell.closest('tr').data('id');
            
            if (newValue !== original) {
                // Visual feedback
                $cell.addClass('text-warning');
                
                // Simulate save action (in a real app, this would be an AJAX call)
                setTimeout(() => {
                    $cell.removeClass('text-warning').addClass('text-success');
                    setTimeout(() => $cell.removeClass('text-success'), 1000);
                }, 500);
                
                console.log(`Saving product ${productId}: ${field} = ${newValue}`);
            }
        });
        
        // Price formatting
        $('#productsTable').on('blur', '.price-cell[contenteditable]', function() {
            const $cell = $(this);
            let value = $cell.text().trim();
            
            // Remove currency symbols and commas
            value = value.replace(/[^\d.]/g, '');
            
            // Parse as float and format
            const num = parseFloat(value);
            if (!isNaN(num)) {
                $cell.text('$' + num.toFixed(2));
            }
        });
        
        // Apply bulk changes
        $('#applyBulkEdit').click(function() {
            const regularPrice = $('#bulkPrice').val();
            const salePrice = $('#bulkSalePrice').val();
            const stockStatus = $('#bulkStockStatus').val();
            
            const changes = {};
            if (regularPrice) changes.regular_price = regularPrice;
            if (salePrice) changes.sale_price = salePrice;
            if (stockStatus) changes.stock_status = stockStatus;
            
            if (Object.keys(changes).length === 0) {
                alert('Please make at least one change');
                return;
            }
            
            const productIds = [];
            table.rows('.selected').every(function() {
                productIds.push($(this.node()).data('id'));
            });
            
            // Show loading
            const btn = $(this);
            btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Applying...');
            btn.prop('disabled', true);
            
            // Simulate bulk save (in real app, AJAX call)
            setTimeout(() => {
                console.log(`Applying changes to ${productIds.length} products:`, changes);
                alert(`Changes applied to ${productIds.length} products!`);
                
                btn.html('Apply Changes');
                btn.prop('disabled', false);
                table.$('tr.selected').removeClass('selected');
                $('#bulkEditPanel').removeClass('active');
                updateSelectedCount();
                
                // Reset form
                $('#bulkPrice, #bulkSalePrice').val('');
                $('#bulkStockStatus').val('');
            }, 1000);
        });
        
        // Bulk delete
        $('#bulkDelete').click(function() {
            const productIds = [];
            table.rows('.selected').every(function() {
                productIds.push($(this.node()).data('id'));
            });
            
            if (productIds.length === 0) {
                alert('Please select at least one product');
                return;
            }
            
            if (!confirm(`Are you sure you want to delete ${productIds.length} products? This cannot be undone.`)) {
                return;
            }
            
            // Show loading
            const btn = $(this);
            btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Deleting...');
            btn.prop('disabled', true);
            
            // Simulate delete (in real app, AJAX call)
            setTimeout(() => {
                console.log(`Deleting ${productIds.length} products:`, productIds);
                alert(`Deleted ${productIds.length} products!`);
                
                btn.html('<i class="fas fa-trash me-1"></i> Delete Selected');
                btn.prop('disabled', false);
                table.rows('.selected').remove().draw();
                $('#bulkEditPanel').removeClass('active');
                updateSelectedCount();
            }, 1000);
        });
        
        // Refresh button
        $('#refreshBtn').click(function() {
            const btn = $(this);
            btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Refreshing...');
            btn.prop('disabled', true);
            
            setTimeout(() => {
                location.reload();
            }, 500);
        });

    // Attribute cell editing
    $('#productsTable').on('click', '.attribute-cell', function(e) {
        if ($(e.target).is('input') || $(e.target).hasClass('autocomplete-results')) {
            return;
        }
        
        const $cell = $(this);
        const $valueDiv = $cell.find('.attribute-value');
        const $inputDiv = $cell.find('.attribute-input');
        
        // Show input, hide value
        $valueDiv.hide();
        $inputDiv.show();
        $inputDiv.find('input').focus();
    });

    //Autocomplete function 
    $('#productsTable').on('input', '.attribute-autocomplete', function() {
        const $input = $(this);
        const taxonomy = $input.data('taxonomy');
        const searchTerm = $input.val().trim();
        const $results = $input.siblings('.autocomplete-results');
        
        if (searchTerm.length < 1) {
            $results.empty().hide();
            return;
        }
        
        // Get results from local storage
        const results = attributeStorage.search(taxonomy, searchTerm);
        
        if (results.length > 0) {
            $results.empty();
            results.forEach(term => {
                $results.append(`
                    <div class="autocomplete-item" 
                        data-id="${term.id}" 
                        data-value="${term.name}">
                        ${term.name}
                    </div>
                `);
            });
            $results.show();
        } else {
            $results.hide();
        }
    });

    // Autocomplete item selection
    $('#productsTable').on('click', '.autocomplete-item', function() {
        const $item = $(this);
        const value = $item.data('value');
        const $input = $item.closest('.attribute-input').find('input');
        const $cell = $item.closest('.attribute-cell');
        
        $input.val(value);
        $item.closest('.autocomplete-results').empty().hide();
        
        // Save immediately on selection
        saveAttributeValue($cell);
    });

    // Add keyboard navigation to autocomplete
    $('#productsTable').on('keydown', '.attribute-autocomplete', function(e) {
        const $input = $(this);
        const $results = $input.siblings('.autocomplete-results');
        const $items = $results.find('.autocomplete-item');
        const $highlighted = $items.filter('.highlighted');
        let index = $items.index($highlighted);
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            index = (index + 1) % $items.length;
            $items.removeClass('highlighted');
            $items.eq(index).addClass('highlighted');
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            index = (index - 1 + $items.length) % $items.length;
            $items.removeClass('highlighted');
            $items.eq(index).addClass('highlighted');
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if ($highlighted.length) {
                $highlighted.click();
            } else if ($items.length) {
                $items.first().click();
            } else {
                // Save current value
                const $cell = $input.closest('.attribute-cell');
                saveAttributeValue($cell);
            }
        } else if (e.key === 'Escape') {
            $results.empty().hide();
            $input.blur();
        }
    });

    // Add hover effect for autocomplete items
    $('#productsTable').on('mouseenter', '.autocomplete-item', function() {
        $(this).siblings().removeClass('highlighted');
        $(this).addClass('highlighted');
    });
    
    // Select autocomplete item
    $('#productsTable').on('click', '.autocomplete-item', function() {
        const $item = $(this);
        const value = $item.data('value');
        const $input = $item.closest('.attribute-input').find('input');
        const $cell = $item.closest('.attribute-cell');
        
        $input.val(value);
        $item.closest('.autocomplete-results').empty().hide();
        
        // Save immediately on selection
        saveAttributeValue($cell);
    });
    
    // Save on blur
    $('#productsTable').on('blur', '.attribute-autocomplete', function() {
        const $input = $(this);
        const $cell = $input.closest('.attribute-cell');
        saveAttributeValue($cell);
    });
    
    // Save attribute value
    function saveAttributeValue($cell) {
        const $input = $cell.find('.attribute-autocomplete');
        const $valueDiv = $cell.find('.attribute-value');
        const newValue = $input.val();
        const taxonomy = $input.data('taxonomy');
        const productId = $cell.data('productid');
        
        // Update display
        $valueDiv.text(newValue);
        $valueDiv.show();
        $cell.find('.attribute-input').hide();
        
        // Visual feedback
        $cell.addClass('saving');

        console.log("Sending attribute save request:", {
            product_id: productId,
            taxonomy: taxonomy,
            value: newValue
        });
        
        // Save to server
        $.ajax({
            url: 'save_product_attribute.php',
            method: 'POST',
            data: {
                product_id: productId,
                taxonomy: taxonomy,
                value: newValue
            },
            success: function() {
                $cell.removeClass('saving').addClass('saved');
                setTimeout(() => $cell.removeClass('saved'), 2000);
            },
            error: function() {
                $cell.removeClass('saving').addClass('error');
                setTimeout(() => $cell.removeClass('error'), 2000);
            }
        });
    }
    
    // Close autocomplete when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.autocomplete-results').length && 
            !$(e.target).hasClass('attribute-autocomplete')) {
            $('.autocomplete-results').empty().hide();
        }
    });

});
</script>


<?php require_once __DIR__.'/includes/footer.php';