<?php
require_once 'config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

$message = $_GET['msg'] ?? '';

// Stats
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM products"))['c'];
$total_users    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users"))['c'];
$total_orders   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders"))['c'];
$total_revenue  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as s FROM orders WHERE status IN ('confirmed','processing','shipped','delivered')"))['s'] ?? 0;

// Low stock alerts
$low_stock = mysqli_query($conn, "SELECT p.id, p.name, p.stock FROM products p WHERE p.stock <= 5 ORDER BY p.stock ASC LIMIT 10");

// Recent orders
$recent_orders = mysqli_query($conn, "SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - MamkatStore</title>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/mamkat.ico">
    <link rel="stylesheet" href="../assets/css/e-commerce.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap:20px; margin-bottom:30px; }
        .stat-card { background:#fff; padding:20px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.06); border-left:4px solid #ff523b; }
        .stat-card h3 { color:#888; font-weight:500; margin-bottom:8px; }
        .stat-card p { font-size:28px; font-weight:700; color:#333; margin:0; }
        .admin-table { width:100%; border-collapse: collapse; background:#fff; }
        .admin-table th { background:#ff523b; color:#fff; padding:12px; text-align:left; }
        .admin-table td { padding:12px; border-bottom:1px solid #eee; }
        .btn-sm { padding:4px 12px; border-radius:20px; font-size:12px; }
        .btn-edit { background:#2980b9; color:#fff; }
        .btn-danger { background:#c0392b; color:#fff; }
        @media (max-width:768px) { .stats-grid { grid-template-columns: repeat(2,1fr); } 
          .nav-tabs a{ display: inline-grid; grid-template-columns: repeat(1,2fr); margin: 1rem;}
          .header {margin-right: 10rem;}
        }
    </style>
</head>
<body>
<div class="-header" style="background:#fff; padding:15px 25px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 10px rgba(0,0,0,0.08);">
    <h1><span style="color:#ff523b;">Mamkat</span>Store Admin</h1>
    <div>
        <a href="<?= BASE_URL ?>/products.php" class="btn btn-sm" style="padding: 9px 12px;">View Store</a>
       <a href="<?= BASE_URL ?>/admin/logout.php" class="btn btn-sm btn-danger" style="margin:0; padding: 9px 12px;">Logout</a>
       <a href="<?= BASE_URL ?>/admin/change-password.php" class="btn btn-sm"><i class="fa fa-key"></i> Change Password</a>
    </div>
</div>

<div style="max-width:1200px; margin:30px auto; padding:0 20px;">
    <?php if ($message): ?>
        <div class="alert alert-success"><?= h($message) ?></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card"><h3>Products</h3><p><?= $total_products ?></p></div>
        <div class="stat-card"><h3>Users</h3><p><?= $total_users ?></p></div>
        <div class="stat-card"><h3>Orders</h3><p><?= $total_orders ?></p></div>
        <div class="stat-card"><h3>Revenue</h3><p>$<?= number_format($total_revenue,2) ?></p></div>
    </div>

    <!-- Low Stock Alerts -->
    <?php if (mysqli_num_rows($low_stock) > 0): ?>
    <div class="alert alert-warning" style="background:#fff3cd; padding:15px; border-radius:10px; margin-bottom:30px;">
        <strong><i class="fa fa-exclamation-triangle"></i> Low Stock Alert</strong>
        <ul>
            <?php while($ls = mysqli_fetch_assoc($low_stock)): ?>
                <li><?= h($ls['name']) ?> - only <?= $ls['stock'] ?> left</li>
            <?php endwhile; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Navigation Tabs -->
    <div class="nav-tabs" style="margin-bottom:20px;">
        <a href="<?= BASE_URL ?>/admin/index.php" style="background:#ff523b; color:#fff; padding:8px 20px; border-radius:20px; margin-right:10px;">Dashboard</a>
        <a href="<?= BASE_URL ?>/admin/products.php" style="background:#eee; padding:8px 20px; border-radius:20px;">Products</a>
        <a href="<?= BASE_URL ?>/admin/orders.php" style="background:#eee; padding:8px 20px; border-radius:20px;">Orders</a>
        <a href="<?= BASE_URL ?>/admin/users.php" style="background:#eee; padding:8px 20px; border-radius:20px;">Users</a>
        <a href="<?= BASE_URL ?>/admin/analytics.php" style="background:#eee; padding:8px 20px; border-radius:20px;">Analytics</a>
        <a href="<?= BASE_URL ?>/admin/testimonials.php" style="background:#eee; padding:8px 20px; border-radius:20px;">Testimonials</a>
    </div>

    <!-- Recent Orders -->
    <h2>Recent Orders</h2>
    <table class="admin-table">
        <thead>
            <tr><th>ID</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php while($o = mysqli_fetch_assoc($recent_orders)): ?>
            <tr>
                <td>#<?= $o['id'] ?></td>
                <td><?= h($o['user_name']) ?></td>
                <td>$<?= number_format($o['total_amount'],2) ?></td>
                <td><span style="padding:3px 10px; border-radius:20px; background:<?= $o['status']=='delivered'?'#d4edda':'#fff3cd' ?>; color:<?= $o['status']=='delivered'?'#155724':'#856404' ?>;"><?= ucfirst($o['status']) ?></span></td>
                <td><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                <td><a href="<?= BASE_URL ?>/admin/orders.php?view=<?= $o['id'] ?>" class="btn btn-sm btn-edit">View</a></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>