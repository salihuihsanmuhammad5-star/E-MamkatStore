<?php
// api/payments/create-monnify-transaction.php
require_once '../../config.php';
require_once '../../includes/helpers.php';
require_once '../../includes/auth.php';
require_once '../../includes/monnify.php';

// ---------- AUTH ----------
if (!isset($_SESSION['user_id'])) {
    json_response(['error' => 'Authentication required'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

if (empty($_SESSION['cart'])) {
    json_response(['error' => 'Cart is empty'], 400);
}

// ---------- CALCULATE TOTAL (USD) ----------
$cart     = $_SESSION['cart'];
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += (float)$item['price'] * (int)$item['quantity'];
}
$tax        = round($subtotal * 0.07, 2);
$total_usd  = round($subtotal + $tax, 2);

// ---------- CONVERT USD → NGN (using exchange rate) ----------
define('USD_TO_NGN_RATE', 1500);   // 1 USD = ₦1500 – change as needed
$total_ngn = $total_usd * USD_TO_NGN_RATE;

// Monnify requires the amount in kobo (minor unit)
$amount_in_kobo = (int) round($total_ngn * 100);

// ---------- ENSURE EMAIL IS NOT EMPTY ----------
$email = trim($_POST['email'] ?? '');
if (empty($email)) {
    // Fallback: get email from database
    $res = mysqli_query($conn, "SELECT email FROM users WHERE id = " . (int)$_SESSION['user_id']);
    if ($row = mysqli_fetch_assoc($res)) {
        $email = $row['email'];
    }
}
if (empty($email)) {
    json_response(['error' => 'Email is required'], 400);
}

// ---------- PAYMENT REFERENCE ----------
$paymentReference = 'MAMKATSTORE_' . $_SESSION['user_id'] . '_' . time() . '_' . bin2hex(random_bytes(4));

// Store the original USD total and the reference for the callback
$_SESSION['monnify_payment_reference'] = $paymentReference;
$_SESSION['monnify_amount']            = $total_usd;    // keep USD amount for order creation

// ---------- INITIALIZE TRANSACTION ----------
$monnify = new MonnifyAPI();

$result = $monnify->initializeTransaction([
    'amount'                => $amount_in_kobo,          // integer, in kobo
    'customerName'          => $_SESSION['user_name'] ?? 'Customer',
    'customerEmail'         => $email,
    'paymentReference'      => $paymentReference,
    'paymentDescription'    => 'Order from MamkatStore',
    'currencyCode'          => 'NGN',                    // NGN only
    'redirectUrl'           => BASE_URL . '/monnify-callback.php'
]);

// ---------- RESPONSE ----------
if (!$result || !($result['requestSuccessful'] ?? false)) {
    json_response([
        'error'   => 'Payment initialization failed',
        'details' => $result['responseMessage'] ?? 'Unknown error'
    ], 500);
}

json_response([
    'success'              => true,
    'checkoutUrl'          => $result['responseBody']['checkoutUrl'] ?? null,
    'transactionReference' => $result['responseBody']['transactionReference'] ?? null
]);