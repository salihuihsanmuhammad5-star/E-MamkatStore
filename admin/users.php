<?php
require_once 'config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

// Toggle admin role
if (isset($_GET['toggle_admin'])) {
    $uid = intval($_GET['toggle_admin']);
    $res = mysqli_query($conn, "SELECT role FROM users WHERE id=$uid");
    $user = mysqli_fetch_assoc($res);
    if ($user) {
        $new_role = $user['role'] === 'admin' ? 'customer' : 'admin';
        mysqli_query($conn, "UPDATE users SET role='$new_role' WHERE id=$uid");
        redirect(BASE_URL . '/admin/users.php?msg=Role+updated');
    }
}

// Disable user (soft delete)
if (isset($_GET['disable'])) {
    $uid = intval($_GET['disable']);
    // We can add a column 'active' TINYINT(1) DEFAULT 1, but for simplicity we just delete (or you can add that column)
    // Here we'll simply delete the user (or you can implement a soft disable)
    mysqli_query($conn, "DELETE FROM users WHERE id=$uid AND role != 'admin'"); // protect admin
    redirect(BASE_URL . '/admin/users.php?msg=User+removed');
}

$users = mysqli_query($conn, "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
$message = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - MamkatStore Admin</title>
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
        .role-badge { padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; background:#eee; }
        .role-admin { background:#ff523b; color:#fff; }
        .btn-sm { padding:4px 12px; border-radius:20px; font-size:12px; cursor:pointer; border:none; text-decoration:none; display:inline-block; }
        .btn-edit { background: #ff523b; color:#fff; }
        .btn-danger { background:#c0392b; color:#fff; }
        span {color: #ff523b;}
        .btn-edit {background: #ff523b;}
    </style>
</head>
<body>
<div style="background:#fff; padding:15px 25px; box-shadow:0 2px 10px rgba(0,0,0,0.08); display:flex; justify-content:space-between; align-items:center;">
    <h2 style="font-size: 30px;">User <span>Management</span></h2>
    <a href="<?= BASE_URL ?>/admin/index.php" class="btn-sm btn-edit">Back to Dashboard</a>
</div>

<div class="admin-wrapper">
    <?php if ($message): ?>
        <div class="alert alert-success"><?= h($message) ?></div>
    <?php endif; ?>
    <div class="panel">
        <div class="panel-header">All Users</div>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php while($u = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= h($u['name']) ?></td>
                        <td><?= h($u['email']) ?></td>
                        <td><span class="role-badge <?= $u['role']=='admin' ? 'role-admin' : '' ?>"><?= ucfirst($u['role']) ?></span></td>
                        <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <?php if ($u['role'] != 'admin'): ?>
                                <a href="?toggle_admin=<?= $u['id'] ?>" class="btn-sm btn-edit">Make Admin</a>
                            <?php else: ?>
                                <a href="?toggle_admin=<?= $u['id'] ?>" class="btn-sm btn-edit" style="padding: 8px;">Demote to Customer</a>
                            <?php endif; ?>
                            <?php if ($u['role'] != 'admin'): ?>
                                <a href="?disable=<?= $u['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>