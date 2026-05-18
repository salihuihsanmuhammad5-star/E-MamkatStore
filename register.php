<?php
require_once 'config.php';
require_once 'includes/csrf.php';
require_once 'includes/helpers.php';

if (isset($_SESSION['user_id'])) redirect(BASE_URL . '/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $name = trim(mysqli_real_escape_string($conn, $_POST['name']));
        $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];

        if (empty($name) || empty($email) || empty($password) || empty($confirm)) $error = 'All fields are required.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Invalid email format.';
        elseif (strlen($password) < 6) $error = 'Password must be at least 6 characters.';
        elseif ($password !== $confirm) $error = 'Passwords do not match.';
        else {
            $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
            if (mysqli_num_rows($check) > 0) {
                $error = 'Email already registered.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param('sss', $name, $email, $hashed);
                if ($stmt->execute()) {
                    redirect(BASE_URL . '/login.php?registered=1');
                } else {
                    $error = 'Registration failed. Try again.';
                }
            }
        }
    }
}
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - MamkatStore</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/e-commerce.css">
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/mamkat.ico">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
<div class="container"><?php include 'includes/navbar.php'; ?></div>
<div class="auth-page">
    <div class="auth-form">
        <h2>Join <span>MamkatStore</span></h2>
        <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" value="<?= h($_POST['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div style="position:relative">
                    <input type="password" name="password" id="regPassword" required>
                    <i class="fa fa-eye toggle-password" data-target="regPassword" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; color:#888;"></i>
                </div>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <div style="position:relative">
                    <input type="password" name="confirm_password" id="regConfirm" required>
                    <i class="fa fa-eye toggle-password" data-target="regConfirm" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; color:#888;"></i>
                </div>
            </div>
            <button type="submit" class="btn">Create Account</button>
        </form>

        <div class="auth-divider"><span>OR</span></div>
        <a href="<?= BASE_URL ?>/auth/google.php" class="btn google-btn"><i class="fa fa-google"></i> Continue with Google</a>
        <div class="auth-switch">Already have an account? <a href="<?= BASE_URL ?>/login.php">Log in</a></div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>