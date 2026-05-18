<?php
require_once 'config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

$message = '';
$error = '';
$edit_product = null;

// Delete
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $res = mysqli_query($conn, "SELECT image FROM products WHERE id=$del_id");
    if ($row = mysqli_fetch_assoc($res)) {
        mysqli_query($conn, "DELETE FROM products WHERE id=$del_id");
        $message = 'Product deleted.';
    }
    redirect(BASE_URL . '/admin/products.php?msg=' . urlencode($message));
}

// Edit load
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$edit_id"));
}

// Add / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(mysqli_real_escape_string($conn, $_POST['name']));
    $price = floatval($_POST['price']);
    $description = trim(mysqli_real_escape_string($conn, $_POST['description']));
    $category = intval($_POST['category_id']);
    $stock = intval($_POST['stock']);
    $rating = floatval($_POST['rating']);
    $featured = isset($_POST['featured']) ? 1 : 0;
    $product_id = intval($_POST['product_id'] ?? 0);

    if (empty($name) || $price <= 0) {
        $error = 'Name and valid price required.';
    } else {
        $image_name = $_POST['existing_image'] ?? '';
        if (!empty($_FILES['image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (in_array($ext, $allowed)) {
                $cat_slug = 'uncategorized';
                $cat_result = mysqli_query($conn, "SELECT slug FROM categories WHERE id = $category");
                if ($cat_row = mysqli_fetch_assoc($cat_result)) {
                    $cat_slug = $cat_row['slug'];
                }
                $target_dir = '../images/products/' . $cat_slug . '/';
                if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
                $new_filename = 'product-' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $new_filename)) {
                    $image_name = 'products/' . $cat_slug . '/' . $new_filename;
                    
                    // Resize uploaded image to a standard size (300x300)
                   if ($ext !== 'gif') {
                      resizeImage($target_dir . $image_name, 300, 300);
                    }
                } else {
                    $error = 'Failed to upload image.';
                    $image_name = $_POST['existing_image'] ?? '';
                }
            } else {
                $error = 'Invalid image format.';
            }
        }

        if (empty($error)) {
            $slug = strtolower(str_replace(' ', '-', $name)) . '-' . rand(100,999);
            if ($product_id > 0) {
                $sql = "UPDATE products SET name='$name', price=$price, description='$description', category_id=$category, stock=$stock, rating=$rating, featured=$featured, image='$image_name', slug='$slug' WHERE id=$product_id";
                $msg = 'Product updated.';
            } else {
                $sql = "INSERT INTO products (name, slug, price, description, category_id, stock, rating, featured, image) VALUES ('$name', '$slug', $price, '$description', $category, $stock, $rating, $featured, '$image_name')";
                $msg = 'Product added.';
            }
            if (mysqli_query($conn, $sql)) {
                redirect(BASE_URL . '/admin/products.php?msg=' . urlencode($msg));
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        }
    }
}

$products = mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id=c.id ORDER BY p.id DESC");
$categories = mysqli_query($conn, "SELECT * FROM categories");

// Notification data
$notification = '';
$notifType = 'success';
if (isset($_GET['msg'])) {
    $notification = $_GET['msg'];
    $notifType = 'success';
} elseif ($error) {
    $notification = $error;
    $notifType = 'error';
}
$showModal = !empty($notification);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products - MamkatStore Admin</title>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/mamkat.ico">
    <link rel="stylesheet" href="../assets/css/e-commerce.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .admin-grid { display: grid; grid-template-columns: 1fr 400px; gap: 30px; margin-top:30px; }
        .panel { background:#fff; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.06); overflow:hidden; }
        .panel-header { background:#ff523b; color:#fff; padding:15px 20px; font-weight:600; }
        .admin-table { width:100%; border-collapse:collapse; }
        .admin-table th { background:#fff3f2; color:#ff523b; padding:12px; text-align:left; }
        .admin-table td { padding:12px; border-bottom:1px solid #f0f0f0; }
        .form-group { margin-bottom:15px; }
        .form-group label { display:block; margin-bottom:5px; color:#555; }
        .form-group input, .form-group textarea, .form-group select { width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; }
        .btn-block { width:100%; }

        /* Top bar */
        .top-bar {
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;
        }
        .top-bar h1 span { color: #ff523b; }

        /* Mobile Add button */
        .mobile-add-btn { display: none; }

        /* Modal styles */
        .modal-overlay {
            position: fixed; top:0; left:0; width:100%; height:100%;
            background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center;
            z-index: 1000;
        }
        .modal-box {
            background: #fff; border-radius: 12px; width: 90%; max-width: 420px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow: hidden;
        }
        .modal-header {
            padding: 15px 20px; font-size: 16px; font-weight: 600; color: #fff;
        }
        .modal-header.success { background: #27ae60; }
        .modal-header.error { background: #c0392b; }
        .modal-body { padding: 20px; font-size: 14px; color: #555; }
        .modal-footer { padding: 15px 20px; text-align: right; border-top: 1px solid #eee; }
        .btn-cancel { background: #888; color: #fff; padding: 8px 20px; border-radius: 5px; cursor: pointer; border: none; }

        /* Responsive: stack and show mobile Add button */
        @media(max-width:768px) {
            .admin-grid { grid-template-columns: 1fr; }
            .form-panel { display: none; }
            .form-panel.visible { display: block; }
            .mobile-add-btn { display: inline-block; }
            .top-bar { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
<div style="max-width:1200px; margin:30px auto; padding:0 20px;">

    <!-- Top bar with Back button and mobile Add button -->
    <div class="top-bar">
        <h1>Manage <span>Products</span></h1>
        <div>
            <a href="<?= BASE_URL ?>/admin/index.php" class="btn" style="margin-right:8px;">
                <i class="fa fa-arrow-left"></i> Back to Dashboard
            </a>
            <button class="btn mobile-add-btn" onclick="toggleForm()">
                <i class="fa fa-plus"></i> Add Product
            </button>
        </div>
    </div>

    <div class="admin-grid">
        <!-- Product list -->
        <div class="panel">
            <div class="panel-header">Product Inventory</div>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php while($p = mysqli_fetch_assoc($products)): ?>
                        <tr>
                            <td><img src="<?= BASE_URL ?>/images/<?= h($p['image']) ?>" style="width:60px;"></td>
                            <td><?= h($p['name']) ?></td>
                            <td><?= h($p['cat_name']) ?></td>
                            <td>$<?= number_format($p['price'],2) ?></td>
                            <td><?= $p['stock'] ?></td>
                            <td>
                                <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-edit">Edit</a>
                                <a href="#" class="btn btn-sm btn-danger" onclick="openDeleteModal(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>'); return false;">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add / Edit form (will have class "visible" on mobile when toggled or editing) -->
        <div class="panel form-panel <?= $edit_product ? 'visible' : '' ?>" id="productForm">
            <div class="panel-header"><?= $edit_product ? 'Edit Product' : 'Add New Product' ?></div>
            <div style="padding:20px;">
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($edit_product): ?>
                        <input type="hidden" name="product_id" value="<?= $edit_product['id'] ?>">
                        <input type="hidden" name="existing_image" value="<?= h($edit_product['image']) ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label>Name *</label>
                        <input type="text" name="name" value="<?= h($edit_product['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Price ($) *</label>
                        <input type="number" step="0.01" name="price" value="<?= $edit_product['price'] ?? '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category_id" required>
                            <?php while($cat = mysqli_fetch_assoc($categories)): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($edit_product['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Stock</label>
                        <input type="number" name="stock" value="<?= $edit_product['stock'] ?? 0 ?>">
                    </div>
                    <div class="form-group">
                        <label>Rating</label>
                        <input type="number" step="0.1" name="rating" min="0" max="5" value="<?= $edit_product['rating'] ?? 4.0 ?>">
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" name="featured" <?= ($edit_product['featured'] ?? '') ? 'checked' : '' ?>> Featured</label>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3"><?= h($edit_product['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Image <?= $edit_product ? '(leave blank to keep)' : '' ?></label>
                        <input type="file" name="image">
                    </div>
                    <button type="submit" class="btn btn-block"><?= $edit_product ? 'Update Product' : 'Add Product' ?></button>
                    <?php if ($edit_product): ?>
                        <a href="<?= BASE_URL ?>/admin/products.php" class="btn btn-cancel" style="display:block; margin-top:10px; background:#888; color:#fff; text-align:center;">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ========== NOTIFICATION MODAL ========== -->
<?php if ($showModal): ?>
<div class="modal-overlay" id="notificationModal">
    <div class="modal-box">
        <div class="modal-header <?= $notifType ?>">
            <?= $notifType == 'success' ? '<i class="fa fa-check-circle"></i> Success' : '<i class="fa fa-exclamation-circle"></i> Error' ?>
        </div>
        <div class="modal-body">
            <p><?= h($notification) ?></p>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeNotification()">OK</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ========== DELETE CONFIRMATION MODAL ========== -->
<div class="modal-overlay" id="deleteModal" style="display:none;">
    <div class="modal-box">
        <div class="modal-header" style="background:#c0392b;"><i class="fa fa-trash"></i> Confirm Delete</div>
        <div class="modal-body">
            <p>Are you sure you want to delete <strong id="deleteProductName"></strong>?</p>
            <p style="color:#999; font-size:12px;">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-danger" id="confirmDeleteBtn">Yes, Delete</button>
            <button class="btn btn-cancel" onclick="closeDeleteModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
// ------ MOBILE FORM TOGGLE ------
function toggleForm() {
    const form = document.getElementById('productForm');
    form.classList.toggle('visible');
    if (form.classList.contains('visible')) {
        form.scrollIntoView({ behavior: 'smooth' });
    }
}
// If editing on mobile, scroll to form
<?php if ($edit_product): ?>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('productForm');
    if (form.classList.contains('visible') && window.innerWidth <= 768) {
        form.scrollIntoView({ behavior: 'smooth' });
    }
});
<?php endif; ?>

// ------ NOTIFICATION MODAL ------
function closeNotification() {
    const modal = document.getElementById('notificationModal');
    if (modal) modal.style.display = 'none';
}

// ------ DELETE MODAL ------
let deleteProductId = null;
function openDeleteModal(id, name) {
    deleteProductId = id;
    document.getElementById('deleteProductName').textContent = name;
    document.getElementById('deleteModal').style.display = 'flex';
}
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    deleteProductId = null;
}
document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (deleteProductId) {
        window.location.href = '<?= BASE_URL ?>/admin/products.php?delete=' + deleteProductId;
    }
});
window.onclick = function(event) {
    const delModal = document.getElementById('deleteModal');
    const notifModal = document.getElementById('notificationModal');
    if (event.target === delModal) closeDeleteModal();
    if (event.target === notifModal) closeNotification();
};
</script>
</body>
</html>

