<?php
// api/cart/remove.php
// Remove a specific item from the cart
require_once '../../config.php';
require_once '../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$product_id = intval($_POST['product_id'] ?? 0);
if ($product_id <= 0) {
    json_response(['error' => 'Invalid product ID'], 400);
}

if (!isset($_SESSION['cart'][$product_id])) {
    json_response(['error' => 'Item not in cart'], 404);
}

unset($_SESSION['cart'][$product_id]);

$cart_count = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_count += $item['quantity'];
}

json_response([
    'success'    => true,
    'cart_count' => $cart_count,
    'message'    => 'Item removed.'
]);