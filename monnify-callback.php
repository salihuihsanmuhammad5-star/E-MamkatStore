<?php
// monnify-callback.php
// Monnify redirects the customer here after payment
require_once 'config.php';
require_once 'includes/helpers.php';
require_once 'includes/auth.php';
require_once 'includes/monnify.php';

// Retrieve the payment reference stored during initialisation
$paymentReference = $_SESSION['monnify_payment_reference'] ?? null;

if (!$paymentReference) {
    // No pending transaction – redirect to cart
    redirect(BASE_URL . '/checkout.php?error=no_pending_transaction');
}

$monnify = new MonnifyAPI();
$verification = $monnify->verifyTransaction($paymentReference);

$success = false;
$message = 'Payment could not be verified. Please contact support.';

if ($verification && ($verification['requestSuccessful'] ?? false)) {
    $status = $verification['responseBody']['paymentStatus'] ?? 'FAILED';

    if ($status === 'PAID' || $status === 'OVERPAID' || $status === 'PARTIALLY_PAID') {
        // Payment confirmed – now create the order in the database
        $user_id  = $_SESSION['user_id'];
        $total    = $_SESSION['monnify_amount'] ?? 0;
        $cart     = $_SESSION['cart'] ?? [];

        if (!empty($cart)) {
            // Insert order
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, payment_method, payment_id, payment_status) VALUES (?, ?, 'confirmed', 'monnify', ?, 'completed')");
            $stmt->bind_param('ids', $user_id, $total, $paymentReference);
            $stmt->execute();
            $order_id = $conn->insert_id;

            // Insert order items
            $stmt2 = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cart as $pid => $item) {
                $stmt2->bind_param('iiid', $order_id, $item['id'], $item['quantity'], $item['price']);
                $stmt2->execute();
                // Decrease stock
                $conn->query("UPDATE products SET stock = stock - {$item['quantity']} WHERE id = {$item['id']}");
            }

            // Clear session cart and payment data
            $_SESSION['cart'] = [];
            unset($_SESSION['monnify_payment_reference'], $_SESSION['monnify_amount']);

            $success = true;
            $message = '';
        }
    } else {
        $message = 'Payment status is ' . $status . '. Please try again.';
    }
}

// Display result page
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