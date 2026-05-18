<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// auth/google-callback.php
require_once __DIR__ . '/../config.php';

$client = new Google\Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);

if (!isset($_GET['code'])) {
    header('Location: ' . BASE_URL . '/login.php?error=google_failed');
    exit;
}


// Exchange code for access token
$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

if (isset($token['error'])) {
    header('Location: ' . BASE_URL . '/login.php?error=google_failed');
    exit;
}

$client->setAccessToken($token['access_token']);

// Get user info
$google_oauth = new Google\Service\Oauth2($client);
$google_user = $google_oauth->userinfo->get();

$email = $google_user->email;
$name  = $google_user->name;
$google_id = $google_user->id;

// Check if user exists
$result = mysqli_query($conn, "SELECT id, name, role FROM users WHERE google_id = '$google_id' OR email = '$email'");
if ($user = mysqli_fetch_assoc($result)) {
    // Existing user: update google_id if not set
    if (empty($user['google_id'])) {
        mysqli_query($conn, "UPDATE users SET google_id = '$google_id', email_verified = 1 WHERE id = {$user['id']}");
    }
    // Log in
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
} else {
    // New user: register
    $hashed = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT); // random password, never used
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, google_id, email_verified) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param('ssss', $name, $email, $hashed, $google_id);
    $stmt->execute();
    $new_id = $conn->insert_id;

    session_regenerate_id(true);
    $_SESSION['user_id']   = $new_id;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_role'] = 'customer';
}

// Redirect to intended page or home
$redirect = $_SESSION['redirect_after_login'] ?? BASE_URL . '/index.php';
unset($_SESSION['redirect_after_login']);
header('Location: ' . $redirect);
exit;