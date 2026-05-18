<?php
// includes/auth.php - require login
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . '/login.php?msg=login_required');
        exit;
    }
}

function require_admin() {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: ' . BASE_URL . '/admin/index.php');
        exit;
    }
}