<?php
require_once 'config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

// Fetch daily revenue for the last 30 days (simplified)
$daily_revenue = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $result = mysqli_query($conn, "SELECT SUM(total_amount) AS total FROM orders WHERE DATE(created_at) = '$date' AND status != 'cancelled'");
    $row = mysqli_fetch_assoc($result);
    $daily_revenue[] = [
        'date' => date('M d', strtotime($date)),
        'revenue' => round($row['total'] ?? 0, 2)
    ];
}

// Top products
$top_products = mysqli_query($conn, "
    SELECT p.name, SUM(oi.quantity) AS sold
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    GROUP BY p.id
    ORDER BY sold DESC
    LIMIT 5
");

// Order status distribution
$status_counts = mysqli_query($conn, "
    SELECT status, COUNT(*) AS total
    FROM orders
    GROUP BY status
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics - MamkatStore Admin</title>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/mamkat.ico">
    <link rel="stylesheet" href="../assets/css/e-commerce.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-wrapper { max-width:1200px; margin:30px auto; padding:0 20px; }
        .chart-container { background:#fff; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.06); padding:20px; margin-bottom:30px; }
        .row-flex { display: flex; gap: 30px; flex-wrap: wrap; }
        .card { flex:1; min-width:300px; background:#fff; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.06); padding:20px; }
        table { width:100%; border-collapse:collapse; }
        th { background:#fff3f2; color:#ff523b; padding:12px; text-align:left; }
        td { padding:12px; border-bottom:1px solid #f0f0f0; }
        span {color: #ff523b;}
        .btn-edit {background: #ff523b;}
    </style>
</head>
<body>
<div style="background:#fff; padding:15px 25px; box-shadow:0 2px 10px rgba(0,0,0,0.08); display:flex; justify-content:space-between; align-items:center;">
    <h2 style="font-size: 30px;">Sales <span>Analytics</span></h2>
    <a href="<?= BASE_URL ?>/admin/index.php" class="btn-sm btn-edit" style="padding: 9px 12px; color: #fff;">Back to Dashboard</a>
</div>

<div class="admin-wrapper">
    <div class="chart-container">
        <h3>Daily Revenue (Last 30 Days)</h3>
        <canvas id="revenueChart" height="100"></canvas>
    </div>

    <div class="row-flex">
        <div class="card">
            <h3>Top Selling Products</h3>
            <table>
                <thead><tr><th>Product</th><th>Units Sold</th></tr></thead>
                <tbody>
                    <?php while($tp = mysqli_fetch_assoc($top_products)): ?>
                        <tr><td><?= h($tp['name']) ?></td><td><?= $tp['sold'] ?></td></tr>
                    <?php endwhile; ?>
                    <?php if(mysqli_num_rows($top_products) == 0): ?>
                        <tr><td colspan="2">No sales data yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card">
            <h3>Order Status Distribution</h3>
            <canvas id="statusChart" height="150"></canvas>
        </div>
    </div>
</div>

<script>
// Revenue chart
const revenueData = <?php echo json_encode(array_column($daily_revenue, 'revenue')); ?>;
const labels = <?php echo json_encode(array_column($daily_revenue, 'date')); ?>;

const ctx1 = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Revenue ($)',
            data: revenueData,
            borderColor: '#ff523b',
            backgroundColor: 'rgba(255,82,59,0.1)',
            tension: 0.3,
            fill: true
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Status pie chart
const statusLabels = [];
const statusCounts = [];
<?php
mysqli_data_seek($status_counts, 0);
while($sc = mysqli_fetch_assoc($status_counts)) {
    echo "statusLabels.push('" . ucfirst($sc['status']) . "');";
    echo "statusCounts.push(" . $sc['total'] . ");";
}
?>
const ctx2 = document.getElementById('statusChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusCounts,
            backgroundColor: ['#ffe0b2','#ffcc80','#ffab40','#ff8a65','#ff7043','#f4511e']
        }]
    }
});
</script>
</body>
</html>
