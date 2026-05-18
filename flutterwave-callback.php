<?php
// flutterwave-callback.php
require_once 'config.php';
require_once 'includes/helpers.php';
require_once 'includes/flutterwave.php';

$tx_ref = $_SESSION['flutterwave_tx_ref'] ?? null;
if (!$tx_ref) {
    redirect(BASE_URL . '/checkout.php?error=no_pending_transaction');
}

// Get transaction_id from query parameter (Flutterwave appends it)
$transaction_id = $_GET['transaction_id'] ?? '';
if (empty($transaction_id)) {
    redirect(BASE_URL . '/checkout.php?error=missing_transaction_id');
}

$flw = new FlutterwaveAPI();
$verification = $flw->verifyTransaction($transaction_id);

if ($verification && ($verification['status'] ?? '') === 'successful') {
    // Payment succeeded – create order
    $user_id  = $_SESSION['user_id'];
    $total    = $_SESSION['flutterwave_amount'] ?? 0;
    $cart     = $_SESSION['cart'] ?? [];

    if (!empty($cart)) {
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, payment_method, payment_id) 
                                VALUES (?, ?, 'confirmed', 'flutterwave', ?)");
        $stmt->bind_param('ids', $user_id, $total, $tx_ref);
        $stmt->execute();
        $order_id = $conn->insert_id;

        // Insert order items & update stock (same as before)
        // ...
        // Insert order items
        $stmt2 = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($cart as $pid => $item) {
            $stmt2->bind_param('iiid', $order_id, $item['id'], $item['quantity'], $item['price']);
            $stmt2->execute();
            // Decrease stock
            $conn->query("UPDATE products SET stock = stock - {$item['quantity']} WHERE id = {$item['id']}");
        }

        // Clear cart & session
        $_SESSION['cart'] = [];
        unset($_SESSION['flutterwave_tx_ref'], $_SESSION['flutterwave_amount']);

        $success = true;
        $message = '';

    }else {
        $message = 'Payment status is ' . $status . '. Please try again.';
    }
}


// Show success/failure page (same layout as monnify-callback.php)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $success ? 'Payment Successful' : 'Payment Failed' ?> - MamkatStore</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/e-commerce.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
<div class="container"><?php include 'includes/navbar.php'; ?></div>

<div class="small-container" style="text-align:center; padding:60px 20px;">
    <?php if ($success): ?>
        <i class="fa fa-check-circle" style="font-size:80px; color:#27ae60; display:block; margin-bottom:20px;"></i>
        <h2>Payment Successful!</h2>
        <p>Your order has been placed. Thank you for shopping with RedStore.</p>
        <div style="margin-top:30px;">
            <a href="<?= BASE_URL ?>/account.php" class="btn">View Orders</a>
            <a href="<?= BASE_URL ?>/index.php" class="btn btn-edit" style="margin-left:15px;">Continue Shopping</a>
        </div>
    <?php else: ?>
        <i class="fa fa-times-circle" style="font-size:80px; color:#c0392b; display:block; margin-bottom:20px;"></i>
        <h2>Payment Failed</h2>
        <p><?= h($message) ?></p>
        <div style="margin-top:30px;">
            <a href="<?= BASE_URL ?>/checkout.php" class="btn">Try Again</a>
            <a href="<?= BASE_URL ?>/cart.php" class="btn btn-danger" style="margin-left:15px;">Back to Cart</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>