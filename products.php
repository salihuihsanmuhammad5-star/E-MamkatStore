<?php
require_once 'config.php';
require_once 'includes/helpers.php';

$per_page = 12;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$where = "1=1";
$category_slug = $_GET['category'] ?? '';
if ($category_slug) {
    $slug = mysqli_real_escape_string($conn, $category_slug);
    $where = "c.slug = '$slug'";
}

$sort = $_GET['sort'] ?? 'newest';
$order = "ORDER BY p.created_at DESC";
if ($sort === 'price_asc') $order = "ORDER BY p.price ASC";
elseif ($sort === 'price_desc') $order = "ORDER BY p.price DESC";
elseif ($sort === 'name') $order = "ORDER BY p.name ASC";

$total_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM products p JOIN categories c ON p.category_id=c.id WHERE $where");
$total_row = mysqli_fetch_assoc($total_res);
$total_products = $total_row['total'];
$total_pages = ceil($total_products / $per_page);

$products = mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id=c.id WHERE $where $order LIMIT $per_page OFFSET $offset");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products - MamkatStore</title>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/mamkat.ico">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/e-commerce.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
<div class="container"><?php include 'includes/navbar.php'; ?></div>

<div class="small-container">
    <div class="row row-2">
        <h2>All Products</h2>
        <div>
            <select id="sortSelect">
                <option value="newest" <?= $sort=='newest'?'selected':'' ?>>Newest</option>
                <option value="price_asc" <?= $sort=='price_asc'?'selected':'' ?>>Price: Low to High</option>
                <option value="price_desc" <?= $sort=='price_desc'?'selected':'' ?>>Price: High to Low</option>
                <option value="name" <?= $sort=='name'?'selected':'' ?>>Name</option>
            </select>
        </div>
    </div>

    <?php if ($category_slug): ?>
        <p>Showing: <strong><?= h($category_slug) ?></strong> <a href="<?= BASE_URL ?>/products.php">(Clear filter)</a></p>
    <?php endif; ?>

    <div class="row">
        <?php while($p = mysqli_fetch_assoc($products)): $stars = round($p['rating']); ?>
        <div class="col-4" onclick="location='<?= BASE_URL ?>/product-details.php?id=<?= $p['id'] ?>'">
            <img src="<?= BASE_URL ?>/images/<?= h($p['image']) ?>" alt="<?= h($p['name']) ?>">
            <h4><?= h($p['name']) ?></h4>
            <div class="rating">
                <?php for($i=1;$i<=5;$i++): ?><i class="fa <?= $i<=$stars?'fa-star':'fa-star-o' ?>"></i><?php endfor; ?>
            </div>
            <p>$<?= number_format($p['price'],2) ?></p>
        </div>
        <?php endwhile; ?>
        <?php if ($total_products == 0): ?>
            <div class="empty-cart"><i class="fa fa-search"></i><h3>No products found</h3></div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <?php
/**
 * Generate an improved pagination with previous/next and limited page numbers.
 * Shows no more than 5 page links at a time, plus ellipsis.
 */
  function generatePaginationLinks(int $currentPage, int $totalPages, array $queryParams, string $baseUrl = ''): string {
    if ($totalPages <= 1) return '';
    
    $html = '<div class="page-btn">';
    
    // Previous button
    if ($currentPage > 1) {
        $prevQuery = array_merge($queryParams, ['page' => $currentPage - 1]);
        $html .= '<a href="?' . http_build_query($prevQuery) . '" class="prev">&#8592; Prev</a>';
    }
    
    $range = 2; // how many pages before/after current
    $firstPage = max(1, $currentPage - $range);
    $lastPage = min($totalPages, $currentPage + $range);
    
    // First page
    if ($firstPage > 1) {
        $query = array_merge($queryParams, ['page' => 1]);
        $html .= '<a href="?' . http_build_query($query) . '">1</a>';
        if ($firstPage > 2) $html .= '<span>…</span>';
    }
    
    // Page numbers
    for ($i = $firstPage; $i <= $lastPage; $i++) {
        $query = array_merge($queryParams, ['page' => $i]);
        $active = ($i == $currentPage) ? ' active' : '';
        $html .= '<a href="?' . http_build_query($query) . '" class="' . $active . '">' . $i . '</a>';
    }
    
    // Last page
    if ($lastPage < $totalPages) {
        if ($lastPage < $totalPages - 1) $html .= '<span>…</span>';
        $query = array_merge($queryParams, ['page' => $totalPages]);
        $html .= '<a href="?' . http_build_query($query) . '">' . $totalPages . '</a>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $nextQuery = array_merge($queryParams, ['page' => $currentPage + 1]);
        $html .= '<a href="?' . http_build_query($nextQuery) . '" class="next">Next &#8594;</a>';
    }
    
    $html .= '</div>';
    return $html;
}

// Call the function with the current page, total pages, and existing GET params
echo generatePaginationLinks($page, $total_pages, $_GET);
?>
    <?php endif; ?>
</div>

<script>
document.getElementById('sortSelect').addEventListener('change', function(){
    const url = new URL(window.location);
    url.searchParams.set('sort', this.value);
    window.location = url;
});
</script>
<?php include 'includes/footer.php'; ?>
</body>
</html>