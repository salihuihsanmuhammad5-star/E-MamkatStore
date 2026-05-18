<?php
// api/auth/register.php
// Create a new customer account
require_once '../../config.php';
require_once '../../includes/helpers.php';

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

// Get and validate input
$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';

if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
    json_response(['error' => 'All fields are required.'], 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['error' => 'Invalid email format.'], 400);
}
if (strlen($password) < 6) {
    json_response(['error' => 'Password must be at least 6 characters.'], 400);
}
if ($password !== $confirm) {
    json_response(['error' => 'Passwords do not match.'], 400);
}

// Check if email already exists
$check = mysqli_query($conn, "SELECT id FROM users WHERE email = '" . mysqli_real_escape_string($conn, $email) . "'");
if (mysqli_num_rows($check) > 0) {
    json_response(['error' => 'Email already registered.'], 409);
}

// Hash password and insert
$hashed = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
$stmt->bind_param('sss', $name, $email, $hashed);

if ($stmt->execute()) {
    json_response(['success' => true, 'message' => 'Registration successful. Please log in.']);
} else {
    json_response(['error' => 'Registration failed. Try again later.'], 500);
}