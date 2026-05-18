<?php
// admin/admin-login.php
require_once dirname(__DIR__) . '/config.php';

// If already logged in as admin, redirect to dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: index.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: admin-login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim(mysqli_real_escape_string($conn, $_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password.';
    } else {
        // Look up user with admin role
        $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        if ($admin && password_verify($password, $admin['password'])) {
            // Login successful: set all required session variables
            session_regenerate_id(true);
            $_SESSION['user_id']    = $admin['id'];
            $_SESSION['user_name']  = $admin['name'];
            $_SESSION['user_role']  = 'admin';
            $_SESSION['admin_logged_in'] = true;  // keep legacy flag

            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid credentials or not an admin.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - MamkatStore</title>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/mamkat.ico">
    <link rel="stylesheet" href="../assets/css/e-commerce.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background: #f4f6f8; }
    </style>
</head>
<body>

<div class="auth-page" style="background: radial-gradient(#fff3f2, #ffd6d6);">
    <div class="auth-form">
        <div style="text-align:center; margin-bottom:25px;">
            <img src="<?= BASE_URL ?>/images/logo1.png" width="200px" alt="MamkatStore Logo">
        </div>
        <h2 style="font-size:22px;">Admin <span style="color:#ff523b;">Login</span></h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="admin@redstore.com" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn" style="width:100%; padding:12px; font-size:15px;">
                <i class="fa fa-sign-in"></i> Login to Admin Panel
            </button>
        </form>
        
        <div style="text-align:center; margin-top:20px;">
            <a href="<?= BASE_URL ?>/products.php" style="color:#ff523b; font-size:13px;">
                <i class="fa fa-arrow-left"></i> Back to Store
            </a>
        </div>
    </div>
</div>

</body>
</html>

