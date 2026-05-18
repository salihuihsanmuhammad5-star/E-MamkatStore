<?php
// api/cart/update.php
// Update quantity or remove item from cart
require_once '../../config.php';
require_once '../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$product_id = intval($_POST['product_id'] ?? 0);
$quantity   = intval($_POST['quantity'] ?? -1);

if ($product_id <= 0 || $quantity < 0) {
    json_response(['error' => 'Invalid parameters'], 400);
}

if (!isset($_SESSION['cart']) || !isset($_SESSION['cart'][$product_id])) {
    json_response(['error' => 'Product not in cart'], 404);
}

if ($quantity === 0) {
    // Remove item
    unset($_SESSION['cart'][$product_id]);
    $message = 'Item removed from cart.';
} else {
    $_SESSION['cart'][$product_id]['quantity'] = $quantity;
    $message = 'Cart updated.';
}

// Recalculate total count
$cart_count = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_count += $item['quantity'];
}

json_response([
    'success'    => true,
    'cart_count' => $cart_count,
    'message'    => $message
]);