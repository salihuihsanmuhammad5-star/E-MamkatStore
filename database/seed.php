<?php
// database/seed-200.php
// Run once to delete all existing products and insert exactly 200 new ones
require_once __DIR__ . '/../config.php';
set_time_limit(60);

// --- Temporarily disable foreign key checks ---
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

// --- Delete all existing products ---
mysqli_query($conn, "DELETE FROM products");
// Reset auto-increment
mysqli_query($conn, "ALTER TABLE products AUTO_INCREMENT = 1");

// --- Re-enable foreign key checks ---
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

// --- Now insert 200 new products ---
$categories = [
    1 => ['name' => 'Fashion Handbags',  'nouns' => ['Tote','Clutch','Satchel','Crossbody','Backpack']],
    2 => ['name' => 'Textiles for Men',    'nouns' => ['Cotton Shirt','Linen Trouser','Kaftan','Dashiki','Agbada']],
    3 => ['name' => 'Textiles for Women',  'nouns' => ['Blouse','Wrapper','Skirt','Dress','Ankara Top']],
    4 => ['name' => 'Laces',               'nouns' => ['Beaded Lace','Swiss Lace','Guipure Lace','Cord Lace','French Lace']],
    5 => ['name' => 'Getzner for Men',     'nouns' => ['Shirt','Trouser','Suit Jacket','Waistcoat']],
    6 => ['name' => 'Abaya for Women',     'nouns' => ['Classic Abaya','Embroidered Abaya','Open Abaya','Kimono Abaya']],
    7 => ['name' => 'Female Children',     'nouns' => ['Frock','Skirt Set','Leggings','Blouse','Dress']],
    8 => ['name' => 'African Traditional (Men)',   'nouns' => ['Agbada Set','Danshiki','Senator Wears','Kente Cloth']],
    9 => ['name' => 'African Traditional (Women)', 'nouns' => ['Boubou','Kente Dress','Ankara Gown','Wrapper Set']]
];

$adjectives = ['Elegant','Classic','Premium','Exclusive','Vintage','Modern','Luxury','Handcrafted','Royal','Trendy'];

$inserted = 0;
for ($i = 0; $i < 200; $i++) {
    $catId = array_rand($categories);
    $cat   = $categories[$catId];
    $adj   = $adjectives[array_rand($adjectives)];
    $noun  = $cat['nouns'][array_rand($cat['nouns'])];
    $num   = rand(100,999);
    $name  = "$adj $noun $num";
    $slug  = strtolower(str_replace(' ', '-', $name)) . '-' . rand(1000,9999);

    $price    = round(rand(1500, 25000) / 100, 2);
    $stock    = rand(0, 100);
    $image    = "products/placeholder.jpg";
    $desc     = "High quality {$cat['name']} product.";
    $rating   = round(rand(30, 50) / 10, 1);
    $featured = (rand(0,1) == 1) ? 1 : 0;

    $sql = "INSERT INTO products (category_id, name, slug, price, stock, image, description, rating, featured)
            VALUES ($catId, '" . mysqli_real_escape_string($conn, $name) . "', '" . mysqli_real_escape_string($conn, $slug) . "', $price, $stock, '" . mysqli_real_escape_string($conn, $image) . "', '" . mysqli_real_escape_string($conn, $desc) . "', $rating, $featured)";
    if (mysqli_query($conn, $sql)) {
        $inserted++;
    }
}
echo "Done! $inserted products inserted with placeholder images.";