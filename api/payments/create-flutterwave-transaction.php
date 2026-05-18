<?php
// api/payments/create-flutterwave-transaction.php
require_once '../../config.php';
require_once '../../includes/helpers.php';
require_once '../../includes/auth.php';
require_once '../../includes/flutterwave.php';

if (!isset($_SESSION['user_id'])) {
    json_response(['error' => 'Authentication required'], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}
if (empty($_SESSION['cart'])) {
    json_response(['error' => 'Cart is empty'], 400);
}

// ---------- CALCULATE TOTAL ----------
$cart = $_SESSION['cart'];
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += (float)$item['price'] * (int)$item['quantity'];
}
$tax   = round($subtotal * 0.07, 2);
$total_usd = round($subtotal + $tax, 2);

// Convert USD to NGN (adjust rate)
$amount_ngn = round($total_usd * 1500, 2);

// ---------- TRANSACTION REFERENCE ----------
$tx_ref = 'MAMKATSTORE_' . $_SESSION['user_id'] . '_' . time();

$_SESSION['flutterwave_tx_ref'] = $tx_ref;
$_SESSION['flutterwave_amount'] = $total_usd;

// ---------- ENSURE EMAIL ----------
$email = trim($_POST['email'] ?? '');
if (empty($email)) {
    // fallback to session user's email from database
    $userRes = mysqli_query($conn, "SELECT email FROM users WHERE id = " . (int)$_SESSION['user_id']);
    $userRow = mysqli_fetch_assoc($userRes);
    $email = $userRow['email'] ?? '';
}
if (empty($email)) {
    json_response(['error' => 'Customer email is missing'], 400);
}

// ---------- INITIALIZE ----------
$flw = new FlutterwaveAPI();
$result = $flw->initializeTransaction([
    'tx_ref'         => $tx_ref,
    'amount'         => $amount_ngn,
    'currency'       => 'NGN',
    'customer_email' => $email,
    'customer_name'  => $_SESSION['user_name'] ?? 'Customer',
    'description'    => 'Order from MamkatStore',
    'redirect_url'   => BASE_URL . '/flutterwave-callback.php'
]);

if (!$result || empty($result['checkout_url'])) {
    // Try to extract the raw error from Flutterwave (if available)
    $detail = 'No checkout URL returned. Possible issues: amount too small, duplicate reference, or invalid currency.';
    // The helper logs the full response – you can check your PHP error log, or we temporarily add a debug here.
    // For a quick debug, re-run the test script (it works) – the problem is likely the amount conversion or email.
    json_response([
        'error'   => 'Payment initialization failed',
        'details' => $detail . ' Amount sent: ' . $amount_ngn . ' NGN, Email: ' . $email
    ], 500);
}

json_response([
    'success'     => true,
    'checkoutUrl' => $result['checkout_url']
]);
