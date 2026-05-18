<?php
// api/cart/add.php
// Add an item to the session cart
require_once '../../config.php';
require_once '../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$product_id = intval($_POST['product_id'] ?? 0);
$quantity   = max(1, intval($_POST['quantity'] ?? 1));

if ($product_id <= 0) {
    json_response(['error' => 'Invalid product ID'], 400);
}

// Fetch product to verify and get details
$stmt = $conn->prepare("SELECT id, name, price, image, stock FROM products WHERE id = ?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    json_response(['error' => 'Product not found'], 404);
}

if ($quantity > $product['stock']) {
    json_response(['error' => 'Requested quantity not available. Only ' . $product['stock'] . ' in stock.'], 400);
}

// Initialize session cart if needed
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add or update cart
if (isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id]['quantity'] += $quantity;
} else {
    $_SESSION['cart'][$product_id] = [
        'id'       => $product['id'],
        'name'     => $product['name'],
        'price'    => $product['price'],
        'image'    => $product['image'],
        'quantity' => $quantity
    ];
}

// Calculate cart count
$cart_count = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_count += $item['quantity'];
}

json_response([
    'success'    => true,
    'cart_count' => $cart_count,
    'message'    => 'Item added to cart.'
]);