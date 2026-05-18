<?php
require_once 'config.php';
require_once 'includes/helpers.php';
require_once 'includes/auth.php';
require_login();

// Fetch user data
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = " . (int)$_SESSION['user_id']));

// Get orders
$orders = mysqli_query($conn,
    "SELECT o.*, COUNT(oi.id) AS item_count
     FROM orders o
     LEFT JOIN order_items oi ON o.id = oi.order_id
     WHERE o.user_id = {$_SESSION['user_id']}
     GROUP BY o.id
     ORDER BY o.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - MamkatStore</title>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/mamkat.ico">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/e-commerce.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        /* Account page styling */
        .account-page {
            padding: 30px 20px 60px;
        }
        .account-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
            align-items: start;
        }

        /* Profile card */
        .account-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .account-header {
            background: linear-gradient(135deg, #ff523b, #c0392b);
            padding: 30px 20px;
            text-align: center;
            color: #fff;
        }
        .account-header i {
            font-size: 60px;
            margin-bottom: 10px;
            display: block;
        }
        .account-header h2 {
            font-size: 24px;
            margin: 5px 0 0;
        }
        .account-header p {
            color: rgba(255,255,255,0.9);
            margin: 5px 0 0;
            font-size: 14px;
        }
        .account-body {
            padding: 25px;
            text-align: center;
        }
        .account-body .btn {
            display: block;
            margin: 10px 0;
            text-align: center;
        }

        /* Orders section */
        .orders-section h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .order-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .order-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .order-info strong {
            color: #333;
            font-size: 16px;
        }
        .order-info small {
            display: block;
            color: #888;
            margin-top: 4px;
        }
        .order-amount {
            font-weight: 700;
            color: #ff523b;
            font-size: 18px;
        }
        .order-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending  { background: #fff3cd; color: #856404; }
        .status-confirmed{ background: #cce5ff; color: #004085; }
        .status-processing{ background: #d1ecf1; color: #0c5460; }
        .status-shipped  { background: #d4edda; color: #155724; }
        .status-delivered{ background: #d4edda; color: #155724; }
        .status-cancelled{ background: #f8d7da; color: #721c24; }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.06);
        }
        .empty-state i {
            font-size: 60px;
            color: #ddd;
            display: block;
            margin-bottom: 15px;
        }

        /* Responsive – stack vertically on mobile */
        @media (max-width: 768px) {
            .account-layout {
                grid-template-columns: 1fr;
            }
            .order-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .order-card .order-amount {
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <?php include 'includes/navbar.php'; ?>
</div>

<div class="small-container account-page">
    <div class="account-layout">

        <!-- Profile Card -->
        <div class="account-card">
            <div class="account-header">
                <i class="fa fa-user-circle"></i>
                <h2><?= h($user['name']) ?></h2>
                <p><?= h($user['email']) ?></p>
            </div>
            <div class="account-body"  style="margin-bottom: 6rem;">
                <p style="font-size:13px; color:#888; margin-bottom:15px;">
                    Member since <?= date('F Y', strtotime($user['created_at'])) ?>
                </p>
                <a href="<?= BASE_URL ?>/products.php" class="btn">Shop Now</a>
                <a href="<?= BASE_URL ?>/logout.php" class="btn" style="background:#c0392b;">Logout</a>
            </div>
        </div>

        <!-- Orders -->
        <div class="orders-section">
            <h2>My Orders</h2>

            <?php if (mysqli_num_rows($orders) === 0): ?>
                <div class="empty-state">
                    <i class="fa fa-shopping-bag"></i>
                    <h3>No orders yet</h3>
                    <p>Start shopping and your orders will appear here.</p>
                    <a href="<?= BASE_URL ?>/products.php" class="btn" style="margin-top:20px;">Browse Products</a>
                </div>
            <?php else: ?>
                <?php while($order = mysqli_fetch_assoc($orders)): ?>
                <div class="order-card">
                    <div class="order-info">
                        <strong>Order #<?= $order['id'] ?></strong>
                        <small>
                            <?= date('M d, Y', strtotime($order['created_at'])) ?> &middot;
                            <?= $order['item_count'] ?> item(s)
                        </small>
                    </div>
                    <div class="order-amount">
                        $<?= number_format($order['total_amount'], 2) ?>
                    </div>
                    <span class="order-status status-<?= $order['status'] ?>">
                        <?= ucfirst($order['status']) ?>
                    </span>
                    <?php if ($order['tracking_number']): ?>
                        <small style="width:100%; color:#888;">Tracking: <?= h($order['tracking_number']) ?></small>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>