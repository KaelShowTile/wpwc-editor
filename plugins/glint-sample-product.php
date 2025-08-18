<?php 

$pageTitle = "Sample Product Setting";

require_once __DIR__.'/../includes/header-sub-page.php'; 
require_once __DIR__.'/../includes/config.php';

?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script type="text/javascript" src="<?php echo tool_url('/assets/js/plugin.js'); ?>" id="products-js"></script>

<div class="container setting-container">

    <div class="setting-header">
        <h3>Setting of CHT Sample Product</h3></br>
        <button id="saveDatebaseSettingBtn" class="btn btn-primary btn-action save-btn">
            <i class="fas fa-save me-1"></i> Save Settings
        </button>
    </div>

    <div class="setting-input-container">
        <p>Do not load sample products in editor</p>
        <div class="form-check me-2">
            <input class="plugin-check-input" type="checkbox" value="disable-sample-product" id="hide-sample-product" >
        </div>
    </div>

</div>


<?php require_once __DIR__.'/includes/footer.php';