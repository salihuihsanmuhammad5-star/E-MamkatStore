<?php
// admin/logout.php
require_once dirname(__DIR__) . '/config.php';
// Destroy the entire session
session_unset();
session_destroy();

// Redirect to admin login page
header('Location: admin-login.php');
exit;