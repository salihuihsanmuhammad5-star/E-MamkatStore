<?php
// models/Product.php
class Product {
    public static function find($id) {
        global $conn;
        $stmt = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function getFeatured($limit = 8) {
        global $conn;
        $stmt = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.featured = 1 ORDER BY p.created_at DESC LIMIT ?");
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function getLatest($limit = 12) {
        global $conn;
        $stmt = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC LIMIT ?");
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function getRelated($product_id, $limit = 4) {
        global $conn;
        $product = self::find($product_id);
        if (!$product) return [];
        $stmt = $conn->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? ORDER BY RAND() LIMIT ?");
        $stmt->bind_param('iii', $product['category_id'], $product_id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function all($filters = [], $sort = 'newest', $page = 1, $per_page = 12) {
        global $conn;
        $where = "1=1";
        $params = [];

        if (!empty($filters['category'])) {
            $where .= " AND c.slug = ?";
            $params[] = $filters['category'];
        }

        $order = "ORDER BY p.created_at DESC";
        switch ($sort) {
            case 'price_asc':  $order = "ORDER BY p.price ASC"; break;
            case 'price_desc': $order = "ORDER BY p.price DESC"; break;
            case 'name':       $order = "ORDER BY p.name ASC"; break;
            case 'rating':     $order = "ORDER BY p.rating DESC"; break;
        }

        $offset = ($page - 1) * $per_page;
        $sql = "SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE $where $order LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;

        $types = str_repeat('s', count($params));
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function countTotal($filters = []) {
        global $conn;
        $where = "1=1";
        $params = [];
        if (!empty($filters['category'])) {
            $where .= " AND c.slug = ?";
            $params[] = $filters['category'];
        }
        $sql = "SELECT COUNT(*) as total FROM products p JOIN categories c ON p.category_id = c.id WHERE $where";
        $stmt = $conn->prepare($sql);
        if ($params) {
            $stmt->bind_param('s', ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'];
    }

    public static function create($data) {
        global $conn;
        $stmt = $conn->prepare("INSERT INTO products (category_id, name, slug, price, stock, image, description, rating, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $slug = strtolower(str_replace(' ', '-', $data['name'])) . '-' . rand(100,999);
        $stmt->bind_param('issdissi', $data['category_id'], $data['name'], $slug, $data['price'], $data['stock'], $data['image'], $data['description'], $data['rating'], $data['featured']);
        $stmt->execute();
        return $conn->insert_id;
    }

    public static function update($id, $data) {
        global $conn;
        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            $fields[] = "`$key` = ?";
            $values[] = $value;
        }
        $values[] = $id;
        $sql = "UPDATE products SET " . implode(',', $fields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $types = str_repeat('s', count($values)); // simplified
        $stmt->bind_param($types, ...$values);
        return $stmt->execute();
    }

    public static function delete($id) {
        global $conn;
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}