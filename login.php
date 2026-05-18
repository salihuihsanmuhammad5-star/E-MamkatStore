<?php
require_once 'config.php';
require_once 'includes/csrf.php';
require_once 'includes/helpers.php';
require_once 'includes/rate_limit.php';

if (isset($_SESSION['user_id'])) redirect(BASE_URL . '/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
        $password = $_POST['password'];
        if (empty($email) || empty($password)) {
            $error = 'All fields are required.';
        } else {
            $result = mysqli_query($conn, "SELECT id, name, password, role FROM users WHERE email = '$email'");
            if ($user = mysqli_fetch_assoc($result)) {
                if (password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];
                    $redir = $_SESSION['redirect_after_login'] ?? BASE_URL . '/index.php';
                    unset($_SESSION['redirect_after_login']);
                    redirect($redir);
                }
            }
            $error = 'Invalid email or password.';
        }
    }if (!rate_limit('login', 5, 60)) {
      $error = 'Too many login attempts. Please try again in a minute.';
    }
}
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MamkatStore</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/e-commerce.css">
    <link rel="icon" type="image/x-icon" href="<?=BASE_URL ?>/images/mamkat.ico">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
<div class="container"><?php include 'includes/navbar.php'; ?></div>
<div class="auth-page">
    <div class="auth-form">
        <h2>Welcome to <span>MamkatStore</span></h2>
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'login_required'): ?>
            <div class="alert alert-error">Please login to continue.</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= h($error) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success">Account created! Please log in.</div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div style="position:relative">
                    <input type="password" name="password" id="loginPassword" required>
                    <i class="fa fa-eye toggle-password" data-target="loginPassword" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; color:#888;"></i>
                </div>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>

        <div class="auth-divider"><span>OR</span></div>
        <a href="<?= BASE_URL ?>/auth/google.php" class="btn google-btn"><i class="fa fa-google"></i> Continue with Google</a>
        <div class="auth-switch">Don't have an account? <a href="<?= BASE_URL ?>/register.php">Create one</a></div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>