<?php 

$pageTitle = " Setting";

require_once __DIR__.'/includes/header.php'; 
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/load_intergrations.php';

$savedPlugins = get_actived_plugins($config['db']['host'], $config['db']['name'], $config['db']['prefix'], $config['db']['user'], $config['db']['password']);

?>

<script type="text/javascript" src="<?php echo tool_url('/assets/js/setting.js'); ?>" id="attribute-js"></script>

<div class="container setting-container">
    
    <div class="d-flex align-items-start">
        <!-- Tab menu -->
        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
            <button class="nav-link active" id="database-setting-tab" data-bs-toggle="pill" data-bs-target="#database-setting-panel" type="button" role="tab" aria-controls="database-setting-panel" aria-selected="true">Database</button>
            <button class="nav-link" id="wordpress-setting-tab" data-bs-toggle="pill" data-bs-target="#wordpress-setting-panel" type="button" role="tab" aria-controls="wordpress-setting-panel" aria-selected="true">WordPress</button>
            <button class="nav-link" id="plugin-setting-tab" data-bs-toggle="pill" data-bs-target="#plugin-setting-panel" type="button" role="tab" aria-controls="plugin-setting-panel" aria-selected="false">Plugin</button>
        </div>
        <!-- Tab content -->
        <div class="tab-content" id="v-pills-tabContent">
            <div class="tab-pane fade show active" id="database-setting-panel" role="tabpanel" aria-labelledby="database-setting-panel" tabindex="0">
                <!-- database setting -->
                <div class="setting-header">
                    <h3>Database Setting</h3></br>
                    <p class="tab-panel-des">Warning: do not changed those setting below if you don't know what they are!</p>
                    <button id="saveDatebaseSettingBtn" class="btn btn-primary btn-action save-btn">
                        <i class="fas fa-save me-1"></i> Save Settings
                    </button>
                </div>
                
                <div class="setting-input-container">
                    <p>Host Name</p>
                    <input type="text" class="form-control" placeholder="localhost" aria-label="host-name" value="<?php echo $config['db']['host'] ?>">
                </div>

                <div class="setting-input-container">
                    <p>Database Name</p>
                    <input type="text" class="form-control" placeholder="wpwc-editor" aria-label="db-name" value="<?php echo $config['db']['name'] ?>">
                </div>

                <div class="setting-input-container">
                    <p>Database User</p>
                    <input type="text" class="form-control" placeholder="root" aria-label="db-user" value="<?php echo $config['db']['user'] ?>">
                </div>

                <div class="setting-input-container">
                    <p>Database Password</p>
                    <input type="text" class="form-control" placeholder="123" aria-label="db-password" value="<?php echo $config['db']['password'] ?>">
                </div>

                <div class="setting-input-container">
                    <p>Database Prefix</p>
                    <input type="text" class="form-control" placeholder="123" aria-label="db-prefix" value="<?php echo $config['db']['prefix'] ?>">
                </div>
            </div>

            <!-- wordpress setting -->
            <div class="tab-pane fade show" id="wordpress-setting-panel" role="tabpanel" aria-labelledby="wordpress-setting-panel" tabindex="0">
                <div class="setting-header">
                    <h3>WordPress Setting</h3></br>
                    <p class="tab-panel-des">Warning: do not changed those setting below if you don't know what they are!</p>
                    <button id="saveWPSettingBtn" class="btn btn-primary btn-action save-btn">
                        <i class="fas fa-save me-1"></i> Save Settings
                    </button>
                </div>

                <div class="setting-input-container">
                    <p>WordPress folder Path</p>
                    <input type="text" class="form-control" placeholder="/public_html" aria-label="wp-path" value="<?php echo $config['wordpress']['path'] ?>">
                </div>

                <div class="setting-input-container">
                    <p>Website Domain</p>
                    <input type="text" class="form-control" placeholder="www.example.com" aria-label="wp-url" value="<?php echo $config['wordpress']['url'] ?>">
                </div>
            </div>
            <!-- plugin setting -->
            <div class="tab-pane fade" id="plugin-setting-panel" role="tabpanel" aria-labelledby="plugin-setting-panel" tabindex="0">
                <div class="setting-header">
                    <h3>Plugin Intergration</h3></br>
                    <p class="tab-panel-des">Tick & active the plugins you want to intergrate with editor.</p>
                    <button id="savePluginBtn" class="btn btn-primary btn-action save-btn">
                        <i class="fas fa-save me-1"></i> Intergrate Plugin
                    </button>
                </div>

                <ul class="avaiable-plugin-list">
                    <li class="avaiable-plugin-item">
                        <div class="form-check me-2">
                            <input class="plugin-check-input" type="checkbox" value="yoast-seo" id="attrCheck0" <?= is_plugin_actived("yoast-seo", $savedPlugins) ? 'checked' : '' ?>>
                        </div>
                        <div class="plugin-info">
                            <img src="/wpwc-editor/assets/img/yoast.jpg">
                            <h5>Yoast SEO</h5>
                        </div>
                    </li>
                    <li class="avaiable-plugin-item">
                        <div class="form-check me-2">
                            <input class="plugin-check-input" type="checkbox" value="acf" id="attrCheck0" <?= is_plugin_actived("acf", $savedPlugins) ? 'checked' : '' ?>>
                        </div>
                        <div class="plugin-info">
                            <img src="/wpwc-editor/assets/img/acf.jpg">
                            <h5>Advanced Custom Fields</h5>
                        </div>
                    </li>
                    <li class="avaiable-plugin-item">
                        <div class="form-check me-2">
                            <input class="plugin-check-input" type="checkbox" value="glint-product-quantity" id="attrCheck0" <?= is_plugin_actived("glint-product-quantity", $savedPlugins) ? 'checked' : '' ?>>
                        </div>
                        <div class="plugin-info">
                            <img src="/wpwc-editor/assets/img/glint-quantity.jpg">
                            <h5>GTO Quantity</h5>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

</div>

<script type="text/javascript" src="<?php echo tool_url('/assets/js/setting.js'); ?>" id="setting-js"></script>
<?php require_once __DIR__.'/includes/footer.php';