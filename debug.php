<?php
// debug.php
// Debug / health‑check script for RedStore eCommerce MIS
// Run this AFTER setting up the database and files.

// Turn on error reporting for the check (do NOT keep on in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'includes/helpers.php';

echo "<h1>RedStore System Debug</h1>";

// 1. Environment variables
echo "<h2>1. Environment Variables</h2>";
echo "<pre>DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NOT SET') . "</pre>";
echo "<pre>DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT SET') . "</pre>";
echo "<pre>BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NOT SET') . "</pre>";
echo "<pre>STRIPE_PUBLISHABLE_KEY: " . (defined('STRIPE_PUBLISHABLE_KEY') ? 'SET (hidden)' : 'MISSING') . "</pre>";
echo "<pre>GOOGLE_CLIENT_ID: " . (defined('GOOGLE_CLIENT_ID') ? 'SET (hidden)' : 'MISSING') . "</pre>";
echo "<pre>MONNIFY_API_KEY: " . (defined('MONNIFY_API_KEY') ? 'SET (hidden)' : 'MISSING') . "</pre>";

// 2. Database connection
echo "<h2>2. Database Connection</h2>";
if ($conn && mysqli_ping($conn)) {
    echo '<p style="color:green;">✔ MySQL connection successful</p>';
} else {
    echo '<p style="color:red;">✘ Database connection failed: ' . mysqli_connect_error() . '</p>';
}

// 3. Required tables
echo "<h2>3. Database Tables</h2>";
$required_tables = [
    'users', 'categories', 'products', 'orders', 'order_items',
    'cart', 'testimonials', 'payments', 'activity_log', 'inventory_alerts'
];
$tables = [];
$result = mysqli_query($conn, "SHOW TABLES");
while ($row = mysqli_fetch_array($result)) {
    $tables[] = $row[0];
}
echo "<ul>";
foreach ($required_tables as $table) {
    if (in_array($table, $tables)) {
        echo "<li style='color:green;'>✔ $table</li>";
    } else {
        echo "<li style='color:red;'>✘ $table MISSING – run schema.sql</li>";
    }
}
echo "</ul>";

// 4. Categories seed
echo "<h2>4. Categories</h2>";
$cat_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM categories"))['c'];
echo "<p>Categories in DB: $cat_count</p>";

// 5. Product count
echo "<h2>5. Products</h2>";
$prod_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM products"))['c'];
echo "<p>Products in DB: $prod_count (target 1500)</p>";

// 6. User count
echo "<h2>6. Users</h2>";
$user_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users"))['c'];
echo "<p>Registered users: $user_count</p>";

// 7. Order count
echo "<h2>7. Orders</h2>";
$order_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders"))['c'];
echo "<p>Total orders: $order_count</p>";

// 8. File structure
echo "<h2>8. File Structure Checks</h2>";
$required_files = [
    'config.php', 'index.php', 'products.php', 'product-details.php', 'cart.php', 'checkout.php',
    'account.php', 'login.php', 'register.php', 'logout.php', 'testimonials.php',
    'admin/admin-login.php', 'admin/index.php', 'admin/products.php', 'admin/orders.php',
    'admin/users.php', 'admin/analytics.php', 'admin/testimonials.php',
    'api/auth/register.php', 'api/auth/login.php', 'api/products/list.php', 'api/products/detail.php',
    'api/cart/add.php', 'api/cart/update.php', 'api/cart/remove.php',
    'api/orders/place.php', 'api/orders/track.php', 'api/testimonials.php',
    'api/payments/create-monnify-transaction.php',
    'auth/google.php', 'auth/google-callback.php',
    'monnify-callback.php',
    'includes/navbar.php', 'includes/footer.php', 'includes/auth.php', 'includes/csrf.php',
    'includes/email.php', 'includes/helpers.php', 'includes/monnify.php',
    'models/User.php', 'models/Product.php', 'models/Order.php', 'models/Cart.php', 'models/Review.php',
    'database/schema.sql', 'database/seed.php'
];

echo "<ul>";
foreach ($required_files as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        echo "<li style='color:green;'>✔ $file</li>";
    } else {
        echo "<li style='color:red;'>✘ $file MISSING</li>";
    }
}
echo "</ul>";

// 9. Session test
echo "<h2>9. Session Configuration</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo '<p style="color:green;">✔ Session is active</p>';
    $_SESSION['debug_test'] = true;
} else {
    echo '<p style="color:red;">✘ Session failed to start</p>';
}

// 10. Quick password hash test
echo "<h2>10. Password Hashing</h2>";
$test_hash = password_hash('test123', PASSWORD_DEFAULT);
if (password_verify('test123', $test_hash)) {
    echo '<p style="color:green;">✔ bcrypt hashing works</p>';
} else {
    echo '<p style="color:red;">✘ Password hashing error</p>';
}

// 11. Composer autoload (Google/Stripe/PHPMailer)
echo "<h2>11. Composer Dependencies</h2>";
if (class_exists('Google\Client')) {
    echo '<p style="color:green;">✔ Google API Client loaded</p>';
} else {
    echo '<p style="color:red;">✘ Google API Client MISSING – run <code>composer require google/apiclient</code></p>';
}
if (class_exists('Stripe\Stripe')) {
    echo '<p style="color:green;">✔ Stripe PHP loaded</p>';
} else {
    echo '<p style="color:red;">✘ Stripe PHP MISSING – run <code>composer require stripe/stripe-php</code></p>';
}
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo '<p style="color:green;">✔ PHPMailer loaded</p>';
} else {
    echo '<p style="color:red;">✘ PHPMailer MISSING – run <code>composer require phpmailer/phpmailer</code></p>';
}

// 12. API endpoint quick tests (optional)
echo "<h2>12. Quick API Tests (GET requests)</h2>";
$test_endpoints = [
    BASE_URL . '/api/products/list.php?per_page=1',
    BASE_URL . '/api/products/list.php?category=fashion-handbags'
];
foreach ($test_endpoints as $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http_code == 200 && $response) {
        echo "<p style='color:green;'>✔ $url – HTTP $http_code</p>";
    } else {
        echo "<p style='color:red;'>✘ $url – HTTP $http_code / no response</p>";
    }
}

echo "<hr>";
echo "<p>Debug completed. If all items are green, the system is ready. If not, fix the missing/broken parts.</p>";