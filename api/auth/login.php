<?php
// api/auth/login.php
// Manual login endpoint
require_once '../../config.php';
require_once '../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    json_response(['error' => 'Email and password required.'], 400);
}

$stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];

    json_response([
        'success' => true,
        'user' => [
            'id'   => $user['id'],
            'name' => $user['name'],
            'role' => $user['role']
        ],
        'redirect' => BASE_URL . '/index.php'
    ]);
} else {
    json_response(['error' => 'Invalid email or password.'], 401);
}