<?php
// models/Order.php
class Order {
    public static function find($id) {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function findByUser($user_id, $limit = 20) {
        global $conn;
        $stmt = $conn->prepare("SELECT o.*, COUNT(oi.id) AS item_count FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE o.user_id = ? GROUP BY o.id ORDER BY o.created_at DESC LIMIT ?");
        $stmt->bind_param('ii', $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function create($user_id, $total, $payment_method = 'cod', $payment_id = null, $payment_status = 'pending') {
        global $conn;
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, payment_method, payment_id, payment_status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('idsss', $user_id, $total, $payment_method, $payment_id, $payment_status);
        if ($stmt->execute()) {
            return $conn->insert_id;
        }
        return false;
    }

    public static function addItem($order_id, $product_id, $quantity, $price) {
        global $conn;
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iiid', $order_id, $product_id, $quantity, $price);
        return $stmt->execute();
    }
}