<?php
// includes/rate_limit.php
// Simple IP‑based rate limiter for login/register endpoints

function rate_limit($action, $maxAttempts = 5, $decaySeconds = 60) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $key = "rate_limit_{$action}_{$ip}";
    $now = time();

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 1, 'first_seen' => $now];
        return true;
    }

    $data = $_SESSION[$key];
    if ($now - $data['first_seen'] > $decaySeconds) {
        // reset window
        $_SESSION[$key] = ['attempts' => 1, 'first_seen' => $now];
        return true;
    }

    if ($data['attempts'] >= $maxAttempts) {
        return false; // rate limit exceeded
    }

    $_SESSION[$key]['attempts']++;
    return true;
}