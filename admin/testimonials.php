<?php
require_once 'config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

// Approve
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    mysqli_query($conn, "UPDATE testimonials SET approved=1 WHERE id=$id");
    redirect(BASE_URL . '/admin/testimonials.php?msg=Approved');
}
// Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM testimonials WHERE id=$id");
    redirect(BASE_URL . '/admin/testimonials.php?msg=Deleted');
}

$testimonials = mysqli_query($conn, "
    SELECT t.*, u.name AS user_name, p.name AS product_name
    FROM testimonials t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN products p ON t.product_id = p.id
    ORDER BY t.created_at DESC
");
$message = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Testimonials - MamkatStore Admin</title>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/mamkat.ico">
    <link rel="stylesheet" href="../assets/css/e-commerce.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .admin-wrapper { max-width:1000px; margin:30px auto; padding:0 20px; }
        .panel { background:#fff; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.06); overflow:hidden; }
        .panel-header { background:#ff523b; color:#fff; padding:15px 20px; font-weight:600; }
        table { width:100%; border-collapse:collapse; }
        th { background:#fff3f2; color:#ff523b; padding:12px; text-align:left; }
        td { padding:12px; border-bottom:1px solid #f0f0f0; }
        .stars i { color:#ff523b; font-size:13px; }
        .btn-sm { padding:4px 12px; border-radius:20px; font-size:12px; text-decoration:none; display:inline-block; }
        .btn-approve { background:#27ae60; color:#fff; }
        .btn-danger { background:#c0392b; color:#fff; }
        span {color: #ff523b;}
        .btn-edit {background: #ff523b;}
    </style>
</head>
<body>
<div style="background:#fff; padding:15px 25px; box-shadow:0 2px 10px rgba(0,0,0,0.08); display:flex; justify-content:space-between; align-items:center;">
    <h2 style="font-size: 30px;">Testimonials <span>Moderation</sapn></h2>
    <a href="<?= BASE_URL ?>/admin/index.php" class="btn-sm btn-edit" style="color: #fff; text-decoration:none; padding: 9px 12px;">Back to Dashboard</a>
</div>

<div class="admin-wrapper">
    <?php if ($message): ?>
        <div class="alert alert-success"><?= h($message) ?></div>
    <?php endif; ?>
    <div class="panel">
        <div class="panel-header">Customer Reviews</div>
        <table>
            <thead>
                <tr><th>User</th><th>Product</th><th>Rating</th><th>Comment</th><th>Date</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php while($t = mysqli_fetch_assoc($testimonials)): ?>
                <tr>
                    <td><?= h($t['user_name']) ?></td>
                    <td><?= $t['product_name'] ? h($t['product_name']) : 'General' ?></td>
                    <td class="stars">
                        <?php for($i=1;$i<=5;$i++): ?>
                            <i class="fa <?= $i<=$t['rating'] ? 'fa-star' : 'fa-star-o' ?>"></i>
                        <?php endfor; ?>
                    </td>
                    <td><?= h($t['comment']) ?></td>
                    <td><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                    <td>
                        <?php if ($t['approved']): ?>
                            <span style="color:#27ae60;">Approved</span>
                        <?php else: ?>
                            <span style="color:#e67e22;">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$t['approved']): ?>
                            <a href="?approve=<?= $t['id'] ?>" class="btn-sm btn-approve">Approve</a>
                        <?php endif; ?>
                        <a href="?delete=<?= $t['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Delete this review?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php if (mysqli_num_rows($testimonials) == 0): ?>
                <tr><td colspan="7" style="text-align:center; padding:30px;">No testimonials yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>