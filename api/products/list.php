<?php
// api/products/list.php
// Paginated product listing with optional filters
require_once '../../config.php';
require_once '../../includes/helpers.php';

// Allow GET only
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 16;
$page     = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset   = ($page - 1) * $per_page;

$where = "1=1";
$params = [];

// Category filter
if (!empty($_GET['category'])) {
    $cat_slug = $_GET['category'];
    $where = "c.slug = ?";
    $params[] = $cat_slug;
}

// Sort
$sort = $_GET['sort'] ?? 'newest';
$order = "ORDER BY p.created_at DESC";
switch ($sort) {
    case 'price_asc':  $order = "ORDER BY p.price ASC"; break;
    case 'price_desc': $order = "ORDER BY p.price DESC"; break;
    case 'name':       $order = "ORDER BY p.name ASC"; break;
    case 'rating':     $order = "ORDER BY p.rating DESC"; break;
}

// Total count
$count_sql = "SELECT COUNT(*) as total FROM products p JOIN categories c ON p.category_id = c.id WHERE $where";
$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];

// Fetch products
$sql = "SELECT p.id, p.name, p.slug, p.price, p.image, p.rating, p.stock, c.name AS category_name, c.slug AS category_slug
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE $where
        $order
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$param_types = str_repeat('s', count($params)) . 'ii';
$all_params = array_merge($params, [$per_page, $offset]);
$stmt->bind_param($param_types, ...$all_params);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $row['image_url'] = BASE_URL . '/images/' . $row['image'];
    $row['rating_dec'] = round($row['rating'], 1);
    $products[] = $row;
}

json_response([
    'products' => $products,
    'pagination' => [
        'current_page' => $page,
        'per_page'     => $per_page,
        'total'        => $total,
        'total_pages'  => ceil($total / $per_page)
    ]
]);