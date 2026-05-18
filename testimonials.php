<?php

require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';
require_once 'includes/csrf.php';
require_login();

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form.';
    } else {
        $rating = intval($_POST['rating']);
        $comment = trim(mysqli_real_escape_string($conn, $_POST['comment']));
        $product_id = $_POST['product_id'] ? intval($_POST['product_id']) : NULL;
        if ($rating < 1 || $rating > 5 || empty($comment)) {
            $error = 'Please provide a rating and comment.';
        } else {
            $stmt = $conn->prepare("INSERT INTO testimonials (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('iiis', $_SESSION['user_id'], $product_id, $rating, $comment);
            if ($stmt->execute()) {
                $success = 'Thank you! Your review is pending approval.';
            } else {
                $error = 'Failed to submit.';
            }
        }
    }
}
$csrf_token = generate_csrf_token();
// For product dropdown (optional)
$products = mysqli_query($conn, "SELECT id, name FROM products ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Write a Testimonial - MamkatStore</title>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/mamkat.ico">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/e-commerce.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
<div class="container"><?php include 'includes/navbar.php'; ?></div>
    <div class="auth-page">
    <div class="auth-form">
    <h2>Write a <span>Testimonial</span></h2>
    <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <div class="form-group">
            <label>Rating</label>
            <select name="rating" style="border-radius: 10px; padding: 10px 150px;" required>
                <option value="5">5 stars</option>
                <option value="4">4 Stars</option>
                <option value="3">3 Stars</option>
                <option value="2">2 Stars</option>
                <option value="1">1 Star</option>
            </select>
        </div>
        <div class="form-group">
            <label>Your Review</label>
            <textarea name="comment" rows="4" style="border-radius: 5px; padding: 8px 95px; border: 1.5px solid; background: #f5ecec; outline: none;" required></textarea>
        </div>
        <div class="form-group" >
            <label>Product (optional)</label>
            <select name="product_id" style="border-radius: 5px; padding: 10px 70px;">
                <option value="" style="text-align: center;">-- General Review --</option>
                <?php while($p = mysqli_fetch_assoc($products)): ?>
                    <option value="<?= $p['id'] ?>"><?= h($p['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" class="btn">Submit Review</button>
    </form>
  </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>