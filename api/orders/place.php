<?php
// api/orders/place.php
// Place an order (requires login)
require_once '../../config.php';
require_once '../../includes/helpers.php';
require_once '../../includes/csrf.php';
require_once '../../includes/auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

// CSRF protection
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    json_response(['error' => 'Invalid security token'], 403);
}

// Check cart
if (empty($_SESSION['cart'])) {
    json_response(['error' => 'Cart is empty'], 400);
}

$user_id = $_SESSION['user_id'];
$cart = $_SESSION['cart'];
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$tax   = $subtotal * 0.07;
$total = $subtotal + $tax;

// Shipping details
$address = trim($_POST['address'] ?? '');
$city    = trim($_POST['city'] ?? '');
$zip     = trim($_POST['zip'] ?? '');
$payment_method = $_POST['payment_method'] ?? 'cod';

if (empty($address) || empty($city)) {
    json_response(['error' => 'Address and city are required.'], 400);
}

// Begin transaction
mysqli_begin_transaction($conn);
try {
    // Insert order
    $shipping = "$address, $city, $zip";
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, payment_method, shipping_address) VALUES (?, ?, 'pending', ?, ?)");
    $stmt->bind_param('idss', $user_id, $total, $payment_method, $shipping);
    $stmt->execute();
    $order_id = $conn->insert_id;

    // Insert order items
    $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($cart as $pid => $item) {
        $stmt_item->bind_param('iiid', $order_id, $item['id'], $item['quantity'], $item['price']);
        $stmt_item->execute();
        // Decrease stock
        $conn->query("UPDATE products SET stock = stock - {$item['quantity']} WHERE id = {$item['id']}");
    }

    // Clear cart
    $_SESSION['cart'] = [];

    mysqli_commit($conn);

    // Send email (optional, using email helper)
    // ...

    json_response([
        'success'  => true,
        'order_id' => $order_id,
        'message'  => 'Order placed successfully.'
    ]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    json_response(['error' => 'Order failed. ' . $e->getMessage()], 500);
}