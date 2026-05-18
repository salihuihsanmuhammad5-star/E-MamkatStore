<?php
// api/orders/track.php
// Get order status and tracking info (requires login)
require_once '../../config.php';
require_once '../../includes/helpers.php';
require_once '../../includes/auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

$order_id = intval($_GET['id'] ?? 0);
if ($order_id <= 0) {
    json_response(['error' => 'Invalid order ID'], 400);
}

// Fetch order belonging to logged-in user
$stmt = $conn->prepare("SELECT o.*, COUNT(oi.id) AS item_count
                        FROM orders o
                        LEFT JOIN order_items oi ON o.id = oi.order_id
                        WHERE o.id = ? AND o.user_id = ?
                        GROUP BY o.id");
$stmt->bind_param('ii', $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    json_response(['error' => 'Order not found or access denied.'], 404);
}

// Also fetch items if needed
$items = $conn->query("SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $order_id")->fetch_all(MYSQLI_ASSOC);

json_response([
    'order' => [
        'id'             => $order['id'],
        'status'         => $order['status'],
        'total'          => $order['total_amount'],
        'payment_method' => $order['payment_method'],
        'tracking'       => $order['tracking_number'],
        'created_at'     => $order['created_at'],
        'item_count'     => $order['item_count'],
        'items'          => $items
    ]
]);