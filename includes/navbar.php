<?php
// includes/navbar.php
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
}
// If logged in, also count DB cart items? We'll keep session cart for simplicity.
?>
<div class="navbar">
    <div class="logo">
        <a href="<?= BASE_URL ?>/index.php"><img src="<?= BASE_URL ?>/images/logo1.png" width="200px" alt="MamkatStore"></a>
    </div>
    <nav>
        <ul id="MenuItems">
            <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
            <li><a href="<?= BASE_URL ?>/products.php">Products</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="<?= BASE_URL ?>/account.php">My Account</a></li>
                <li><a href="<?= BASE_URL ?>/logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="<?= BASE_URL ?>/login.php">Login</a></li>
                <li><a href="<?= BASE_URL ?>/register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <a href="<?= BASE_URL ?>/cart.php" class="cart-icon-link">
        <img src="<?= BASE_URL ?>/images/cart.png" width="30px" height="30px" alt="Cart">
        <?php if ($cart_count > 0): ?>
            <span class="cart-count"><?= $cart_count ?></span>
        <?php endif; ?>
    </a>
    <img src="<?= BASE_URL ?>/images/menu.png" class="menu-icon" onclick="menutoggle()" alt="Menu">
</div>