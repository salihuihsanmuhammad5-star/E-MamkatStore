<?php
// models/User.php
class User {
    public static function find($id) {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function findByEmail($email) {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function create($name, $email, $password, $google_id = null) {
        global $conn;
        if ($google_id) {
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, google_id, email_verified) VALUES (?, ?, ?, ?, 1)");
            $hashed = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
            $stmt->bind_param('ssss', $name, $email, $hashed, $google_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $name, $email, password_hash($password, PASSWORD_DEFAULT));
        }
        if ($stmt->execute()) {
            return $conn->insert_id;
        }
        return false;
    }

    public static function update($id, $data) {
        global $conn;
        // Simple dynamic update – production code would use a whitelist
        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            $fields[] = "`$key` = ?";
            $values[] = $value;
        }
        $values[] = $id;
        $sql = "UPDATE users SET " . implode(',', $fields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $types = str_repeat('s', count($values));
        $stmt->bind_param($types, ...$values);
        return $stmt->execute();
    }
}