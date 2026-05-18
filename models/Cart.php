<?php
// models/Cart.php
class Cart {
    // Session cart helpers
    public static function getCart() {
        return $_SESSION['cart'] ?? [];
    }

    public static function add($product_id, $quantity, $product_data) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'id'       => $product_id,
                'name'     => $product_data['name'],
                'price'    => $product_data['price'],
                'image'    => $product_data['image'],
                'quantity' => $quantity
            ];
        }
    }

    public static function remove($product_id) {
        unset($_SESSION['cart'][$product_id]);
    }

    public static function updateQuantity($product_id, $quantity) {
        if ($quantity <= 0) {
            self::remove($product_id);
        } else {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
        }
    }

    public static function clear() {
        $_SESSION['cart'] = [];
    }

    public static function totalItems() {
        $count = 0;
        foreach (self::getCart() as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }

    public static function subtotal() {
        $subtotal = 0;
        foreach (self::getCart() as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        return $subtotal;
    }
}