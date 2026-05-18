<?php
require_once 'config.php';
require_once 'includes/helpers.php';

// Remove item
if (isset($_GET['remove'])) {
    $rid = intval($_GET['remove']);
    unset($_SESSION['cart'][$rid]);
    redirect(BASE_URL . '/cart.php');
}

// Update quantities
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $pid => $qty) {
        $pid = intval($pid);
        $qty = intval($qty);
        if ($qty > 0 && isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid]['quantity'] = $qty;
        } elseif ($qty <= 0 && isset($_SESSION['cart'][$pid])) {
            unset($_SESSION['cart'][$pid]);
        }
    }
    redirect(BASE_URL . '/cart.php');
}

// Clear cart
if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    redirect(BASE_URL . '/cart.php');
}

$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$tax = $subtotal * 0.07;
$total = $subtotal + $tax;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cart - MamkatStore</title>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/mamkat.ico">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/e-commerce.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
<div class="container"><?php include 'includes/navbar.php'; ?></div>
<div class="small-container cart-page">
    <?php if (empty($cart)): ?>
        <div class="empty-cart" style="margin-bottom: 8rem;">
            <i class="fa fa-shopping-cart"></i>
            <h3>Your cart is empty</h3>
            <a href="<?= BASE_URL ?>/products.php" class="btn">Continue Shopping</a>
        </div>
    <?php else: ?>
        <form method="POST">
            <table>
                <tr><th>Product</th><th>Quantity</th><th>Subtotal</th></tr>
                <?php foreach ($cart as $pid => $item): ?>
                <tr>
                    <td>
                        <div class="cart-info">
                            <img src="<?= BASE_URL ?>/images/<?= h($item['image']) ?>" alt="<?= h($item['name']) ?>">
                            <div>
                                <p><?= h($item['name']) ?></p>
                                <small>Price: $<?= number_format($item['price'],2) ?></small>
                                <br><a href="<?= BASE_URL ?>/cart.php?remove=<?= $pid ?>">Remove</a>
                            </div>
                        </div>
                    </td>
                    <td><input type="number" name="quantities[<?= $pid ?>]" value="<?= $item['quantity'] ?>" min="0" style="width:60px;"></td>
                    <td>$<?= number_format($item['price'] * $item['quantity'],2) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <div style="display:flex; justify-content:space-between; margin-top:20px;">
                <button type="submit" name="update_cart" class="btn">Update Cart</button>
                <a href="<?= BASE_URL ?>/cart.php?clear=1" class="btn btn-danger">Clear Cart</a>
            </div>
        </form>
        <div class="total-price">
            <table>
                <tr><td>Subtotal</td><td>$<?= number_format($subtotal,2) ?></td></tr>
                <tr><td>Tax (7%)</td><td>$<?= number_format($tax,2) ?></td></tr>
                <tr><td>Total</td><td><strong>$<?= number_format($total,2) ?></strong></td></tr>
            </table>
            <a href="<?= BASE_URL ?>/checkout.php" class="btn" style="margin-top:65px; padding: 12px; margin-left: 1rem;">Proceed to Checkout</a>
        </div>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
</body>

</html>