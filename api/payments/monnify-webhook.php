<?php
// api/payments/monnify-webhook.php
require_once '../../config.php';
require_once '../../includes/helpers.php';
require_once '../../includes/monnify.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

// Verify the webhook signature (Monnify sends a hash in the header)
$body = file_get_contents('php://input');
$signature = $_SERVER['HTTP_MONNIFY_SIGNATURE'] ?? '';

$computedHash = hash_hmac('sha512', $body, MONNIFY_SECRET_KEY);
if (!hash_equals($computedHash, $signature)) {
    json_response(['error' => 'Invalid signature'], 403);
}

$event = json_decode($body, true);
$paymentReference = $event['eventData']['paymentReference'] ?? null;

if ($paymentReference && ($event['eventType'] ?? '') === 'SUCCESSFUL_TRANSACTION') {
    // Find the saved reference and create the order
    // (implementation depends on your data storage strategy)

    // If you stored a temporary row, create the order and send email
}

json_response(['success' => true]);

