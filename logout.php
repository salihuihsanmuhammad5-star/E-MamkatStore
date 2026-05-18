<?php
require_once 'config.php';
require_once 'includes/helpers.php';
session_unset();
session_destroy();
redirect(BASE_URL . '/login.php');