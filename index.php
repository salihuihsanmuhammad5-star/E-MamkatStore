<?php
require_once 'config.php';
require_once 'includes/helpers.php';

$featured = mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.featured = 1 ORDER BY p.id DESC LIMIT 4");
$latest = mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC LIMIT 8");
$testimonials = mysqli_query($conn, "SELECT t.*, u.name FROM testimonials t JOIN users u ON t.user_id = u.id WHERE t.approved = 1 ORDER BY t.created_at DESC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MamkatStore - African Fashion & Textiles</title>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/mamkat.ico">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/e-commerce.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>

<div class="header">
    <div class="container" style="margin-bottom: 2rem;">
        <?php include 'includes/navbar.php'; ?>
        <div class="row">
            <div class="col-2">
                <h1 style="font-size: 65px;">Discover African Elegance</h1>
                <p>Handcrafted textiles, handbags, laces, abayas & traditional wears for everyone.</p>
                <a href="<?= BASE_URL ?>/products.php" class="btn">Explore Now &#8594;</a>
            </div>
            <div class="col-2">
                <img src="<?= BASE_URL ?>/images/image.png" alt="African Fashion">
            </div>
        </div>
    </div>
</div>

<!-- Categories -->


<!-- Featured Products -->
<div class="small-container">
    <h2 class="title">Featured Products</h2>
    <div class="row">
        <?php while($p = mysqli_fetch_assoc($featured)): $s = round($p['rating']); ?>
        <div class="col-4" onclick="location='<?= BASE_URL ?>/product-details.php?id=<?= $p['id'] ?>'">
            <img src="<?= BASE_URL ?>/images/<?= h($p['image']) ?>" alt="<?= h($p['name']) ?>">
            <h4><?= h($p['name']) ?></h4>
            <div class="rating">
                <?php for($i=1;$i<=5;$i++): ?>
                    <i class="fa <?= $i<=$s ? 'fa-star' : 'fa-star-o' ?>"></i>
                <?php endfor; ?>
            </div>
            <p>$<?= number_format($p['price'],2) ?></p>
        </div>
        <?php endwhile; ?>
    </div>

    <h2 class="title">Latest Products</h2>
    <div class="row">
        <?php while($p = mysqli_fetch_assoc($latest)): $s = round($p['rating']); ?>
        <div class="col-4" onclick="location='<?= BASE_URL ?>/product-details.php?id=<?= $p['id'] ?>'">
            <img src="<?= BASE_URL ?>/images/<?= h($p['image']) ?>" alt="<?= h($p['name']) ?>">
            <h4><?= h($p['name']) ?></h4>
            <div class="rating">
                <?php for($i=1;$i<=5;$i++): ?>
                    <i class="fa <?= $i<=$s ? 'fa-star' : 'fa-star-o' ?>"></i>
                <?php endfor; ?>
            </div>
            <p>$<?= number_format($p['price'],2) ?></p>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Testimonials -->
<div class="testimonial">
    <div class="small-container">
        <h2 class="title">Customer Testimonials</h2>
        <div class="row">
            <?php while($t = mysqli_fetch_assoc($testimonials)): ?>
            <div class="col-3">
                <i class="fa fa-quote-left"></i>
                <p><?= h($t['comment']) ?></p>
                <div class="rating">
                    <?php for($i=1;$i<=5;$i++): ?>
                        <i class="fa <?= $i<=$t['rating'] ? 'fa-star' : 'fa-star-o' ?>"></i>
                    <?php endfor; ?>
                </div>
                <img src="<?= BASE_URL ?>/images/user-1.png" alt="User">
                <h3><?= h($t['name']) ?></h3>
            </div>
            <?php endwhile; ?>
        </div>
        <?php if (isset($_SESSION['user_id'])): ?>
        <div style="text-align:center; margin-top:20px;">
            <a href="<?= BASE_URL ?>/testimonials.php" class="btn" style="margin-bottom: 10rem;">Write a Review</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>

