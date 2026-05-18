<?php
// models/Review.php
class Review {
    public static function getApproved($limit = 5) {
        global $conn;
        $stmt = $conn->prepare("SELECT t.*, u.name AS user_name FROM testimonials t JOIN users u ON t.user_id = u.id WHERE t.approved = 1 ORDER BY t.created_at DESC LIMIT ?");
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function submit($user_id, $rating, $comment, $product_id = null) {
        global $conn;
        $stmt = $conn->prepare("INSERT INTO testimonials (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iiis', $user_id, $product_id, $rating, $comment);
        return $stmt->execute();
    }

    public static function approve($id) {
        global $conn;
        return $conn->query("UPDATE testimonials SET approved=1 WHERE id=" . intval($id));
    }

    public static function delete($id) {
        global $conn;
        return $conn->query("DELETE FROM testimonials WHERE id=" . intval($id));
    }
}