<?php
// database/create-admin.php
// Run once to create the first admin user
require_once __DIR__ . '/../config.php';

$email    = 'admin@mamkatstore.com';      // change if desired
$password = 'Mamkat@123';               // change to a strong password
$name     = 'Super Admin';

// Check if admin already exists
$result = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
if (mysqli_num_rows($result) > 0) {
    // Update role to admin if exists
    mysqli_query($conn, "UPDATE users SET role = 'admin' WHERE email = '$email'");
    echo "User '$email' already exists. Role updated to admin.<br>";
} else {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
    $stmt->bind_param('sss', $name, $email, $hashed);
    if ($stmt->execute()) {
        echo "Admin user created: $email / $password<br>";
    } else {
        echo "Failed to create admin.<br>";
    }
}
echo "<a href='" . BASE_URL . "/admin/admin-login.php'>Go to Admin Login</a>";