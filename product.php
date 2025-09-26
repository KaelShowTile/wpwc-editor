<?php 

$pageTitle = "Products";

require_once __DIR__.'/includes/header.php'; 
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/load_intergrations.php';

// Load WordPress environment
require_once $config['wordpress']['path'].'/wp-load.php';

//for media libary
wp_enqueue_media();

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

$yoast_seo_active = false;
if(is_plugin_actived("yoast-seo", $savedPlugins)){
    $yoast_seo_active = true;
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
<script type="text/javascript" src="<?php echo tool_url('/assets/js/product-attribute.js'); ?>" id="products-attribute-js"></script>
<script type="text/javascript" src="<?php echo tool_url('/assets/js/product-saving.js'); ?>" id="product-saving-js"></script>
<script type="text/javascript" src="<?php echo tool_url('/assets/js/product-adding-new.js'); ?>" id="product-adding-new-js"></script>
<script type="text/javascript" src="<?php echo tool_url('/assets/js/media-library.js'); ?>" id="media-library-js"></script>

<?php if($glint_product_quantity_active == true): ?>
<script type="text/javascript" src="<?php echo tool_url('/assets/js/glint-quantity-saving.js'); ?>" id="glint-quantity-saving-js"></script>
<?php endif; ?>

<?php if($yoast_seo_active == true): ?>
<script type="text/javascript" src="<?php echo tool_url('/assets/js/yoast-seo-saving.js'); ?>" id="yoast-seo-saving-js"></script>
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
                <button class="btn btn-success btn-action" id="AddNewProduct">
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

<!-- Media Library Modal -->
<div class="modal fade" id="mediaLibraryModal" tabindex="-1" aria-labelledby="mediaLibraryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mediaLibraryModalLabel">Media Library</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" id="mediaTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="media-library-tab" data-bs-toggle="tab" data-bs-target="#media-library" type="button" role="tab" aria-controls="media-library" aria-selected="true">
                            <i class="fas fa-images me-1"></i> Media Library
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload" type="button" role="tab" aria-controls="upload" aria-selected="false">
                            <i class="fas fa-upload me-1"></i> Upload Files
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="mediaTabContent">
                    <!-- Media Library Tab -->
                    <div class="tab-pane fade show active" id="media-library" role="tabpanel" aria-labelledby="media-library-tab">
                        
                        <div class="row">

                            <div class="col-9">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6>Media Library</h6>
                                    <div class="d-flex align-items-center">
                                        <span id="mediaCount" class="badge bg-secondary me-2">Loading...</span>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="refreshMedia">
                                                <i class="fas fa-sync-alt"></i> Refresh
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row" id="mediaLibraryItems">
                                    <div class="col-12 text-center py-5">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2">Loading media library...</p>
                                    </div>
                                </div>
                                
                                <div class="row mt-3" id="loadMoreMediaContainer" style="display: none;">
                                    <div class="col-12 text-center">
                                        <button type="button" class="btn btn-outline-primary" id="loadMoreMedia">
                                            Load More Images
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Preview Panel -->
                            <div class="col-3" id="previewPanel">
                                <div class="card">
                                    <div class="card-header">Image Preview</div>
                                    <div class="card-body text-center">
                                        <div id="mediaPreviewContainer">
                                            <span class="text-muted">Select an image to preview</span>
                                        </div>
                                        <div class="mt-3" id="mediaSelectionInfo">
                                            <p class="small text-muted">No image selected</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    
                    <!-- Upload Tab -->
                    <div class="tab-pane fade" id="upload" role="tabpanel" aria-labelledby="upload-tab">
                        <div class="row">
                            <div class="col-9">
                                <h6>Upload New File</h6>
                                <p class="text-muted">Select a file to upload to your media library.</p>
                                
                                <div class="mb-3">
                                    <label for="mediaUpload" class="form-label">Choose File</label>
                                    <input class="form-control" type="file" id="mediaUpload" accept="image/*">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="imageTitle" class="form-label">Image Title</label>
                                    <input type="text" class="form-control" id="imageTitle" placeholder="Enter image title">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="imageAltText" class="form-label">Alt Text</label>
                                    <input type="text" class="form-control" id="imageAltText" placeholder="Enter alt text for accessibility">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="imageDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="imageDescription" rows="2" placeholder="Optional description"></textarea>
                                </div>
                                
                                <div class="progress mb-3" id="uploadProgress" style="display: none;">
                                    <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                                </div>
                                
                                <div class="text-center">
                                    <button type="button" class="btn btn-primary" id="uploadMediaBtn" disabled>
                                        <i class="fas fa-upload me-1"></i> Upload Image
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Preview Panel -->
                            <div id="uploadPreviewContainer" class="text-center col-3" style="display: none;">
                                <h6>Image Preview</h6>
                                <div id="uploadPreview" class="border rounded">
                                    <span class="text-muted">Preview will appear here</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="selectGalleryBtn" disabled>Add to Gallery</button>
                <button type="button" class="btn btn-primary" id="selectMediaBtn" disabled>Select Image</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Product Panel -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addProductForm">
                    <ul class="nav nav-tabs" id="productTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">General</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="gallery-tab" data-bs-toggle="tab" data-bs-target="#gallery" type="button" role="tab" aria-controls="gallery" aria-selected="false">Images</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pricing-tab" data-bs-toggle="tab" data-bs-target="#pricing" type="button" role="tab" aria-controls="pricing" aria-selected="false">Pricing</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory" type="button" role="tab" aria-controls="inventory" aria-selected="false">Inventory</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="shipping-tab" data-bs-toggle="tab" data-bs-target="#shipping" type="button" role="tab" aria-controls="shipping" aria-selected="false">Shipping</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="attributes-tab" data-bs-toggle="tab" data-bs-target="#attributes" type="button" role="tab" aria-controls="attributes" aria-selected="false">Attributes</button>
                        </li>
                    </ul>
                    <div class="tab-content p-3" id="productTabsContent">
                        <!-- General Tab -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="productName" class="form-label required-field">Product Name</label>
                                        <input type="text" class="form-control" id="productName" name="product_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="shortDescription" class="form-label">Short Description</label>
                                        <textarea class="form-control" id="shortDescription" name="short_description" rows="3"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="productDescription" class="form-label">Description</label>
                                        <textarea class="form-control" id="productDescription" name="product_description" rows="15"></textarea>
                                    </div>   
                                </div>
                                <div class="col-md-4">                                   
                                    <div class="mb-3">
                                        <label class="form-label">Categories</label>
                                        <div class="search-box">
                                            <input type="text" class="form-control" id="categorySearch" placeholder="Search categories...">
                                        </div>
                                        <div class="category-container" id="categoryContainer">
                                            <!-- Categories will be loaded here via AJAX -->
                                            <div class="text-center py-3">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2">Loading categories...</p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Add Tags
                                    <div class="mb-3">
                                        <label for="productTags" class="form-label">Tags</label>
                                        <input type="text" class="form-control" id="productTags" name="product_tags" placeholder="Separate tags with commas">
                                    </div>
                                    -->
                                </div>
                            </div>
                        </div>

                        <!-- Product image & gallery Tab -->
                        <div class="tab-pane fade" id="gallery" role="tabpanel" aria-labelledby="gallery-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Product Cover Image</label>
                                        <div class="product-image-preview mb-2" id="imagePreview">
                                            <span class="text-muted">No image selected</span>
                                        </div>
                                        <input type="hidden" id="productImageId" name="product_image_id" value="">
                                        <button type="button" class="btn btn-secondary" id="mediaLibraryBtn">
                                            <i class="fas fa-images me-1"></i> Select from Media Library
                                        </button>
                                        <button type="button" class="btn btn-danger" id="removeImageBtn" style="display: none;">
                                            <i class="fas fa-times me-1"></i> Remove Image
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Gallery</label>
                                    <div class="gallery-preview mb-2" id="galleryPreview">
                                        <span class="text-muted">No gallery images selected</span>
                                    </div>
                                    <input type="hidden" id="productGalleryIds" name="product_gallery_ids" value="">
                                    <button type="button" class="btn btn-secondary" id="galleryLibraryBtn">
                                        <i class="fas fa-images me-1"></i> Select Gallery Images
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing Tab -->
                        <div class="tab-pane fade" id="pricing" role="tabpanel" aria-labelledby="pricing-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="regularPrice" class="form-label">Regular Price ($)</label>
                                        <input type="number" class="form-control" id="regularPrice" name="regular_price" step="0.01" min="0">
                                    </div>
                                    <div class="mb-3">
                                        <label for="salePrice" class="form-label">Sale Price ($)</label>
                                        <input type="number" class="form-control" id="salePrice" name="sale_price" step="0.01" min="0">
                                    </div>
                                </div>
                                <!-- Tax setting 
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="taxable" name="taxable" checked>
                                            <label class="form-check-label" for="taxable">Taxable</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="taxClass" class="form-label">Tax Class</label>
                                        <select class="form-select" id="taxClass" name="tax_class">
                                            <option value="standard">Standard</option>
                                            <option value="reduced">Reduced Rate</option>
                                            <option value="zero">Zero Rate</option>
                                        </select>
                                    </div>
                                </div>
                                -->
                            </div>
                        </div>
                        
                        <!-- Inventory Tab -->
                        <div class="tab-pane fade" id="inventory" role="tabpanel" aria-labelledby="inventory-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="sku" class="form-label">SKU</label>
                                        <input type="text" class="form-control" id="sku" name="sku" placeholder="Stock Keeping Unit">
                                    </div>
                                    <div class="mb-3">
                                        <label for="stockStatus" class="form-label">Stock Status</label>
                                        <select class="form-select" id="stockStatus" name="stock_status">
                                            <option value="instock">In Stock</option>
                                            <option value="outofstock">Out of Stock</option>
                                            <option value="onbackorder">On Backorder</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="manageStock" class="form-label">Manage Stock</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="manageStock" name="manage_stock">
                                            <label class="form-check-label" for="manageStock">Enable stock management at product level</label>
                                        </div>
                                    </div>
                                    <div class="mb-3" id="stockQuantityContainer" style="display: none;">
                                        <label for="stockQuantity" class="form-label">Stock Quantity</label>
                                        <input type="number" class="form-control" id="stockQuantity" name="stock_quantity" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Shipping Tab -->
                        <div class="tab-pane fade" id="shipping" role="tabpanel" aria-labelledby="shipping-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="weight" class="form-label">Weight (kg)</label>
                                        <input type="number" class="form-control" id="weight" name="weight" step="0.01" min="0">
                                    </div>
                                    <div class="mb-3">
                                        <label for="dimensions" class="form-label">Dimensions (cm)</label>
                                        <div class="row">
                                            <div class="col-4">
                                                <input type="number" class="form-control" id="length" name="length" placeholder="Length" step="0.1" min="0">
                                            </div>
                                            <div class="col-4">
                                                <input type="number" class="form-control" id="width" name="width" placeholder="Width" step="0.1" min="0">
                                            </div>
                                            <div class="col-4">
                                                <input type="number" class="form-control" id="height" name="height" placeholder="Height" step="0.1" min="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Attributes Tab -->
                        <div class="tab-pane fade" id="attributes" role="tabpanel" aria-labelledby="attributes-tab">
                            <div class="mb-3">
                                <label class="form-label">Product Attributes</label>
                                <div id="attributesContainer">
                                    <div class="attribute-row mb-2">
                                        <div class="row">
                                            <div class="col-5">
                                                <input type="text" class="form-control" name="attribute_names[]" placeholder="Attribute name (e.g. Color)">
                                            </div>
                                            <div class="col-5">
                                                <input type="text" class="form-control" name="attribute_values[]" placeholder="Values (separated by | )">
                                            </div>
                                            <div class="col-2">
                                                <button type="button" class="btn btn-danger remove-attribute"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-secondary mt-2" id="addAttribute">
                                    <i class="fas fa-plus me-1"></i> Add Attribute
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitProduct">Add Product</button>
            </div>
        </div>
    </div>
</div>

<!-- Success Toast -->
<div class="toast position-fixed top-0 end-0 m-3" id="successToast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
    <div class="toast-header bg-success text-white">
        <strong class="me-auto">Success</strong>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">
        Product added successfully!
    </div>
</div>

<!-- Error Toast -->
<div class="toast position-fixed top-0 end-0 m-3" id="errorToast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
    <div class="toast-header bg-danger text-white">
        <strong class="me-auto">Error</strong>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="errorMessage">
        There was an error adding the product.
    </div>
</div>

<!-- Show/Hide Cols-->
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
            <?php if($yoast_seo_active == true):?>
            <li><input class="column-check-input" type="checkbox" value="show" id="hide-yoast-seo" checked=""><span>Yoast SEO</span></li>
            <?php endif; ?> 
        </ul>
    </div>
</div>

<!-- Products Table -->
<div class="container wpwc-list-container">

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
                        <th width="120" class="description-col">Short Description</th>
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
                        <?php if($yoast_seo_active == true):?>
                        <th width="160" class="yoast-col">Yoast SEO Title</th>
                        <th width="200" class="yoast-col">Yoast SEO Description</th>
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
                            p.post_excerpt,
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
                    
                    if($yoast_seo_active == true){
                        $product_load_string .=",
                            MAX(CASE WHEN pm1.meta_key = '_yoast_wpseo_title' THEN pm1.meta_value END) AS yoast_seo_title,
                            MAX(CASE WHEN pm1.meta_key = '_yoast_wpseo_metadesc' THEN pm1.meta_value END) AS yoast_seo_desc";
                    }

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
                        AND p.post_status IN ('publish', 'private')
                        GROUP BY p.ID";

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

                        <!-- Short Description -->
                        <td class="editable-cell description-col short-desc-col" contenteditable="true" data-field="post_excerpt" data-productid="<?= $product->ID ?>" data-original="<?= esc_attr($product->post_excerpt) ?>"><?= esc_html($product->post_excerpt) ?></td>

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

                        <?php if($yoast_seo_active == true):?>

                        <!-- Yoast SEO Title -->
                        <td class="seo-title-col">
                            <div class="editable-field yoast-seo-title" 
                                contenteditable="true"
                                data-field="_yoast_wpseo_title"
                                data-productid="<?= $product->ID ?>"
                                data-original="<?= esc_attr($product->yoast_seo_title) ?>">
                                <?= esc_html(($product->yoast_seo_title)) ?>         
                            </div>
                        </td>

                        <!-- Yoast SEO Description -->
                        <td class="yoast-col">
                            <div class="editable-field yoast-des-title" 
                                contenteditable="true"
                                data-field="_yoast_wpseo_metadesc"
                                data-productid="<?= $product->ID ?>"
                                data-original="<?= esc_attr($product->yoast_seo_desc) ?>">
                                <?= esc_html(($product->yoast_seo_desc)) ?>         
                            </div>
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