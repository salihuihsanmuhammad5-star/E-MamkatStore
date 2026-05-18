<?php
require_once 'config.php';
require_once 'includes/helpers.php';

$id = intval($_GET['id'] ?? 0);
$result = mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id=c.id WHERE p.id = $id");
$product = mysqli_fetch_assoc($result);
if (!$product) redirect(BASE_URL . '/products.php');

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $qty = max(1, intval($_POST['quantity']));
    $pid = $product['id'];
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    if (isset($_SESSION['cart'][$pid])) {
        $_SESSION['cart'][$pid]['quantity'] += $qty;
    } else {
        $_SESSION['cart'][$pid] = [
            'id' => $pid,
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'quantity' => $qty
        ];
    }
    redirect(BASE_URL . '/product-details.php?id=' . $pid . '&added=1');
}

$stars = round($product['rating']);
$related = mysqli_query($conn, "SELECT * FROM products WHERE category_id = {$product['category_id']} AND id != $id LIMIT 4");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= h($product['name']) ?> - MamkatStore</title>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/mamkat.ico">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/e-commerce.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
<div class="container"><?php include 'includes/navbar.php'; ?></div>

<?php if (isset($_GET['added'])): ?>
    <div style="background:#e8f8e8; color:#27ae60; padding:12px 25px; text-align:center;">
        ✓ Item added to cart! <a href="<?= BASE_URL ?>/cart.php">View Cart</a>
    </div>
<?php endif; ?>

<div class="small-container single-product">
    <div class="row">
        <div class="col-2">
            <img src="<?= BASE_URL ?>/images/<?= h($product['image']) ?>" width="100%" id="ProductImg" alt="<?= h($product['name']) ?>">
            <!-- gallery (optional) -->
        </div>
        <div class="col-2">
            <p>Home / <?= h($product['cat_name']) ?></p>
            <h2><?= h($product['name']) ?></h2>
            <div class="rating">
                <?php for($i=1;$i<=5;$i++): ?><i class="fa <?= $i<=$stars?'fa-star':'fa-star-o' ?>"></i><?php endfor; ?>
            </div>
            <h4>$<?= number_format($product['price'],2) ?></h4>
            <?php if ($product['stock'] > 0): ?>
                <span style="color:#27ae60;">In Stock (<?= $product['stock'] ?> available)</span>
            <?php else: ?>
                <span style="color:#c0392b;">Out of Stock</span>
            <?php endif; ?>

            <form method="POST" style="margin-top:20px;">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock'] > 0 ? $product['stock'] : 0 ?>" <?= $product['stock'] <= 0 ? 'disabled' : '' ?> style="width:60px;">
                <button type="submit" name="add_to_cart" class="btn" <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>Add to Cart</button>
            </form>

            <h3 style="margin-top:20px;">Product Details</h3>
            <p><?= nl2br(h($product['description'])) ?></p>
        </div>
    </div>
</div>

<!-- Related Products -->
<?php if (mysqli_num_rows($related) > 0): ?>
<div class="small-container">
    <h2 class="title">Related Products</h2>
    <div class="row">
        <?php while($r = mysqli_fetch_assoc($related)): $rs = round($r['rating']); ?>
        <div class="col-4" onclick="location='<?= BASE_URL ?>/product-details.php?id=<?= $r['id'] ?>'">
            <img src="<?= BASE_URL ?>/images/<?= h($r['image']) ?>" alt="<?= h($r['name']) ?>">
            <h4><?= h($r['name']) ?></h4>
            <div class="rating">
                <?php for($i=1;$i<=5;$i++): ?><i class="fa <?= $i<=$rs?'fa-star':'fa-star-o' ?>"></i><?php endfor; ?>
            </div>
            <p>$<?= number_format($r['price'],2) ?></p>
        </div>
        <?php endwhile; ?>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
</body>
</html>