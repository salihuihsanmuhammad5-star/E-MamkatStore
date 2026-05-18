<?php
// api/testimonials.php
// Handle testimonial submissions and listing
require_once '../config.php';
require_once '../includes/helpers.php';
require_once '../includes/csrf.php';

$method = $_SERVER['REQUEST_METHOD'];

// ------------------- SUBMIT (requires login) -------------------
if ($method === 'POST') {
    require_login();

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        json_response(['error' => 'Invalid security token'], 403);
    }

    $rating     = intval($_POST['rating'] ?? 0);
    $comment    = trim($_POST['comment'] ?? '');
    $product_id = $_POST['product_id'] ? intval($_POST['product_id']) : null;

    if ($rating < 1 || $rating > 5 || empty($comment)) {
        json_response(['error' => 'Rating (1-5) and comment are required.'], 400);
    }

    $stmt = $conn->prepare("INSERT INTO testimonials (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('iiis', $_SESSION['user_id'], $product_id, $rating, $comment);
    if ($stmt->execute()) {
        json_response(['success' => true, 'message' => 'Review submitted. Pending approval.']);
    } else {
        json_response(['error' => 'Failed to submit review.'], 500);
    }
}

// ------------------- LIST (public) -------------------
if ($method === 'GET') {
    $approved = mysqli_query($conn, "SELECT t.id, t.rating, t.comment, t.created_at u.name AS user_name, p.name AS product_name
                                     FROM testimonials t
                                     JOIN users u ON t.user_id = u.id
                                     LEFT JOIN products p ON t.product_id = p.id
                                     WHERE t.approved = 1
                                     ORDER BY t.created_at DESC
                                     LIMIT 20");
    $list = [];
    while ($row = mysqli_fetch_assoc($approved)) {
        $list[] = $row;
    }
    json_response(['testimonials' => $list]);
}
json_response(['error' => 'Method not allowed'], 405);

