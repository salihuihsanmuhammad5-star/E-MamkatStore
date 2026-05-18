<?php
// api/products/detail.php
// Get a single product by ID
require_once '../../config.php';
require_once '../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    json_response(['error' => 'Invalid product ID'], 400);
}

$stmt = $conn->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug
                        FROM products p
                        JOIN categories c ON p.category_id = c.id
                        WHERE p.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    json_response(['error' => 'Product not found'], 404);
}

// Add full image URL
$product['image_url'] = BASE_URL . '/images/' . $product['image'];
$product['rating_dec'] = round($product['rating'], 1);

// Get related products (same category, exclude self)
$stmt2 = $conn->prepare("SELECT id, name, slug, price, image, rating FROM products WHERE category_id = ? AND id != ? LIMIT 4");
$stmt2->bind_param('ii', $product['category_id'], $id);
$stmt2->execute();
$related = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($related as &$r) {
    $r['image_url'] = BASE_URL . '/images/' . $r['image'];
}

json_response([
    'product' => $product,
    'related' => $related
]);