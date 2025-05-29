<?php 

require_once __DIR__.'/../functions.php'; 
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$base_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');

?>

<!DOCTYPE html>
<html>
<head>
    <title>WPWC Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <base href="<?= $base_url ?>/">
</head>
<body>
<div class="container mt-5">

    <div class="install-header">
        <h1>Welcome to WPWC editor</h1>
        <p>Please setup a databse for the WPWC editor & fill in your WordPress website detail</p>
    </div>
    
    <form action="<?= tool_url('processor.php') ?>" method="post" class="install-form">
        <h3 class="mt-4">Database Configuration</h3>
        <div class="mb-3">
            <label class="form-label">Host</label>
            <input type="text" name="db_host" value="localhost" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Database Name</label>
            <input type="text" name="db_name" value="wp_tool_db" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="db_user" value="root" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="db_pass" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Table Prefix</label>
            <input type="text" name="db_prefix" value="wptool_" class="form-control">
        </div>

        <h3 class="mt-4">WordPress Information</h3>
        <div class="mb-3">
            <label class="form-label">WordPress Path (Absolute)</label>
            <input type="text" name="wp_path" value="C:/xampp/htdocs/wordpress" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">WordPress URL</label>
            <input type="url" name="wp_url" value="http://localhost/wordpress" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Install</button>
    </form>
</div>
</body>
</html>