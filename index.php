<?php 


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = "Home";

require_once __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/includes/header.php';

if (!empty($_SESSION['login_error'])) {
    echo '<div style="
        background: #ffebee;
        border-left: 4px solid #f44336;
        padding: 12px;
        margin: 20px 0;
    ">'.htmlspecialchars($_SESSION['login_error']).'</div>';
    unset($_SESSION['login_error']);
}

?>

<div class="container">

    <div class="intro-container">
        <h1>Product Editor</h1>
    </div>
    
    <div class="login-form-container">
        <?php if (!isset($_SESSION['wpe_authenticated'])): 
            $error = $_SESSION['login_error'] ?? '';
            unset($_SESSION['login_error']);
        ?>
            <div class="login-box">
                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <h2>WordPress Login</h2>
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <label class="hide-this-area"><input type="checkbox" name="remember"> Remember me </label>
                    <button type="submit" name="wpe_login" value="1">Login</button>
                </form>
            </div>
        <?php else:?>
            
        <?php endif; ?>
    </div>

</div>


<?php require_once __DIR__.'/includes/footer.php';


