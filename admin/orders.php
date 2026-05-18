<?php
require_once 'config.php'; // auto-check admin
require_once dirname(__DIR__) . '/includes/helpers.php';

// Update order status or tracking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = intval($_POST['order_id']);
    if (isset($_POST['update_status'])) {
        $new_status = mysqli_real_escape_string($conn, $_POST['status']);
        $tracking = mysqli_real_escape_string($conn, $_POST['tracking_number'] ?? '');
        $sql = "UPDATE orders SET status='$new_status', tracking_number='$tracking' WHERE id=$order_id";
        mysqli_query($conn, $sql);
        redirect(BASE_URL . '/admin/orders.php?msg=Order+updated');
    }
}

// Fetch all orders
$orders = mysqli_query($conn, "
    SELECT o.*, u.name AS customer_name, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
");

$message = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Orders - MamkatStore Admin</title>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/mamkat.ico">
    <link rel="stylesheet" href="../assets/css/e-commerce.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .admin-wrapper { max-width:1200px; margin:30px auto; padding:0 20px; }
        .panel { background:#fff; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.06); overflow:hidden; }
        .panel-header { background:#ff523b; color:#fff; padding:15px 20px; font-weight:600; }
        table { width:100%; border-collapse:collapse; }
        th { background:#fff3f2; color:#ff523b; padding:12px; text-align:left; }
        td { padding:12px; border-bottom:1px solid #f0f0f0; }
        .status-badge { padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600; }
        .status-pending { background:#fff3cd; color:#856404; }
        .status-confirmed, .status-processing { background:#cce5ff; color:#004085; }
        .status-shipped { background:#d4edda; color:#155724; }
        .status-delivered { background:#d4edda; color:#155724; }
        .status-cancelled { background:#f8d7da; color:#721c24; }
        .btn-sm { padding:4px 12px; border-radius:20px; font-size:12px; cursor:pointer; border:none; }
        .btn-edit { background:#2980b9; color:#fff; }
        .btn-danger { background:#c0392b; color:#fff; }
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; }
        .modal-content { background:#fff; margin:5% auto; padding:20px; width:400px; border-radius:10px; }
        .form-group { margin-bottom:15px; }
        .form-group label { display:block; margin-bottom:5px; color:#555; }
        .form-group input, .form-group select { width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; }
        span {color: #ff523b;}
        .btn-edit {background: #ff523b;}
    </style>
</head>
<body>
<div style="background:#fff; padding:15px 25px; box-shadow:0 2px 10px rgba(0,0,0,0.08); display:flex; justify-content:space-between; align-items:center;">
    <h2 style="font-size: 30px;">Order <span>Management</span></h2>
    <a href="<?= BASE_URL ?>/admin/index.php" class="btn-sm btn-edit" style="text-decoration:none; padding: 9px 12px;">Back to Dashboard</a>
</div>

<div class="admin-wrapper">
    <?php if ($message): ?>
        <div class="alert alert-success"><?= h($message) ?></div>
    <?php endif; ?>

    <div class="panel">
        <div class="panel-header">All Orders</div>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Tracking</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($o = mysqli_fetch_assoc($orders)): ?>
                    <tr>
                        <td><strong>#<?= $o['id'] ?></strong></td>
                        <td><?= h($o['customer_name']) ?></td>
                        <td><?= h($o['email']) ?></td>
                        <td>$<?= number_format($o['total_amount'],2) ?></td>
                        <td><?= h($o['payment_method']) ?></td>
                        <td>
                            <span class="status-badge status-<?= $o['status'] ?>">
                                <?= ucfirst($o['status']) ?>
                            </span>
                        </td>
                        <td><?= h($o['tracking_number'] ?? '—') ?></td>
                        <td><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                        <td>
                            <button onclick='openModal(<?= json_encode($o) ?>)' class="btn-sm btn-edit">
                                <i class="fa fa-edit"></i> Update
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (mysqli_num_rows($orders) == 0): ?>
                        <tr><td colspan="9" style="text-align:center; padding:30px;">No orders yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal for updating order -->
<div id="orderModal" class="modal">
    <div class="modal-content">
        <h3>Update Order #<span id="modalOrderId"></span></h3>
        <form method="POST">
            <input type="hidden" name="order_id" id="formOrderId">
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="formStatus">
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="form-group">
                <label>Tracking Number (optional)</label>
                <input type="text" name="tracking_number" id="formTracking">
            </div>
            <button type="submit" name="update_status" class="btn btn-block">Save Changes</button>
            <button type="button" class="btn btn-block" onclick="closeModal()" style="background:#888; margin-top:5px;">Cancel</button>
        </form>
    </div>
</div>

<script>
function openModal(order) {
    document.getElementById('modalOrderId').textContent = order.id;
    document.getElementById('formOrderId').value = order.id;
    document.getElementById('formStatus').value = order.status;
    document.getElementById('formTracking').value = order.tracking_number || '';
    document.getElementById('orderModal').style.display = 'block';
}
function closeModal() {
    document.getElementById('orderModal').style.display = 'none';
}
window.onclick = function(event) {
    if (event.target == document.getElementById('orderModal')) {
        closeModal();
    }
}
</script>
</body>
</html>