<?php
/**
 * RedStore Checkout Page
 * 
 * Supports:
 * - Cash on Delivery (COD)      → immediate order creation
 * - Stripe (Card)                → confirmed via Stripe Elements (if enabled)
 * - Monnify (Moniepoint)         → redirect to Monnify, order created in monnify-callback.php
 */
require_once 'config.php';
require_once 'includes/helpers.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';
require_once 'includes/email.php';

// ---------- AUTHENTICATION ----------
require_login();

// Fetch the logged-in user’s details
// Fetch user details for pre‑filling the form
$user = mysqli_fetch_assoc(mysqli_query($conn, " SELECT name, email FROM users WHERE id = " . (int)$_SESSION['user_id']));

// ---------- CART VALIDATION ----------
if (empty($_SESSION['cart'])) {
    redirect(BASE_URL . '/cart.php');
}

$cart = $_SESSION['cart'];
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$tax   = $subtotal * 0.07;
$total = $subtotal + $tax;

$order_success = false;
$error = '';

// ---------- HANDLE FORM SUBMISSION (COD & STRIPE ONLY) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // CSRF
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $user_id = $_SESSION['user_id'];
        $payment_method = $_POST['payment_method'] ?? 'cod';

        // Block Monnify from direct POST – it must be handled via AJAX/redirect
        if (in_array($payment_method, ['monnify', 'flutterwave'])) {
            $error = 'Please complete your payment using the provided checkout button.';
        }else {
            // ----- COD / Stripe order creation -----
            $address = trim($_POST['address'] ?? '');
            $city    = trim($_POST['city'] ?? '');
            $zip     = trim($_POST['zip'] ?? '');

            if (empty($address) || empty($city)) {
                $error = 'Address and city are required.';
            } else {
                $shipping = "$address, $city, $zip";
                $stripe_payment_id = $_POST['stripe_payment_id'] ?? null;
                $payment_status    = ($payment_method === 'stripe') ? 'completed' : 'pending';

                // Insert order
                $stmt = $conn->prepare(
                    "INSERT INTO orders (user_id, total_amount, status, payment_method, payment_id, payment_status, shipping_address)
                     VALUES (?, ?, 'pending', ?, ?, ?, ?)"
                );
                $stmt->bind_param('idssss', $user_id, $total, $payment_method, $stripe_payment_id, $payment_status, $shipping);

                if ($stmt->execute()) {
                    $order_id = $conn->insert_id;

                    // Insert order items & update stock
                    $stmt_item = $conn->prepare(
                        "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)"
                    );
                    foreach ($cart as $pid => $item) {
                        $stmt_item->bind_param('iiid', $order_id, $item['id'], $item['quantity'], $item['price']);
                        $stmt_item->execute();
                        $conn->query("UPDATE products SET stock = stock - {$item['quantity']} WHERE id = {$item['id']}");
                    }

                    // Clear cart
                    $_SESSION['cart'] = [];

                    // Send confirmation email (optional)
                    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name, email FROM users WHERE id = $user_id"));
                    if ($user) {
                        $subject = "Order #{$order_id} Confirmation";
                        $body = "<h2>Thank you for your order, {$user['name']}!</h2>
                                 <p>Your order total is <strong>$".number_format($total,2)."</strong>.</p>
                                 <p>We will notify you when your order ships.</p>";
                        send_email($user['email'], $subject, $body);
                    }

                    $order_success = true;
                } else {
                    $error = 'Failed to place order. Please try again.';
                }
            }
        }
    }
}

// Generate CSRF token for the form
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - MamkatStore</title>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/mamkat.ico">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/e-commerce.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

    <!-- Stripe JS (only needed for Stripe) -->
    <?php if (defined('STRIPE_PUBLISHABLE_KEY') && STRIPE_PUBLISHABLE_KEY !== ''): ?>
    <script src="https://js.stripe.com/v3/"></script>
    <?php endif; ?>

    <style>
        /* Additional styles for checkout */
        #monnify-info ul { margin: 10px 0 0 20px; font-size: 14px; }
        .alert-error { background: #fde8e8; color: #c0392b; border: 1px solid #f5c6c6; padding: 12px 20px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="container">
    <?php include 'includes/navbar.php'; ?>
</div>

<div class="small-container checkout-page">

    <?php if ($order_success): ?>
        <!-- SUCCESS: Order placed (COD / Stripe) -->
        <div style="text-align:center; padding:60px 20px;">
            <i class="fa fa-check-circle" style="font-size:80px; color:#27ae60; display:block; margin-bottom:20px;"></i>
            <h2>Order Placed Successfully!</h2>
            <p>Thank you for your purchase. You will receive an email confirmation shortly.</p>
            <div style="margin-top:30px;">
                <a href="<?= BASE_URL ?>/index.php" class="btn">Continue Shopping</a>
                <a href="<?= BASE_URL ?>/account.php" class="btn btn-edit" style="margin-left:15px;">View Orders</a>
            </div>
        </div>

    <?php else: ?>
        <h2 style="margin-bottom:30px; color:#333;">Checkout</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="checkoutForm">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <div class="checkout-grid">

                <!-- Billing Details -->
                <div class="checkout-form">
                    <h3>Shipping Details</h3>
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" value="<?= h($_SESSION['user_name'] ?? $user['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" value="<?= h($user['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Street Address *</label>
                        <input type="text" name="address" placeholder="123 Main Street" required>
                    </div>
                    <div class="form-group">
                        <label>City *</label>
                        <input type="text" name="city" placeholder="Your city" required>
                    </div>
                    <div class="form-group">
                        <label>Postal Code</label>
                        <input type="text" name="zip" placeholder="12345">
                    </div>

                    <!-- Payment Method -->
                    <h3 style="margin-top:30px;">Payment Method</h3>
                    <div class="form-group">
                        <label>
                            <input type="radio" name="payment_method" value="cod" checked onchange="togglePaymentMethod()">
                            Cash on Delivery
                        </label><br>
                        <label>
                            <input type="radio" name="payment_method" value="stripe" onchange="togglePaymentMethod()">
                            Credit Card (Stripe)
                        </label><br>
                        <label>
                            <input type="radio" name="payment_method" value="monnify" onchange="togglePaymentMethod()">
                            <strong>Moniepoint (Bank Transfer / Card / USSD)</strong>
                        </label>
                        <label>
                          <input type="radio" name="payment_method" value="flutterwave" onchange="togglePaymentMethod()">
                          <strong>Pay with Flutterwave (Card, Bank Transfer, USSD)</strong>
                       </label>
                    </div>

                    <!-- COD info -->
                    <div id="cod-info" style="padding:20px; background:#fff8f8; border:1.5px dashed #ff523b; border-radius:8px; color:#555;">
                        <i class="fa fa-money"></i> Pay when your order arrives.
                    </div>

                    <!-- Stripe Card Element -->
                    <div id="stripe-section" style="display:none; margin-top:15px;">
                        <div id="card-element" style="padding:10px; border:1px solid #ccc; border-radius:5px;"></div>
                        <div id="card-errors" role="alert" style="color:#c0392b; margin-top:10px;"></div>
                        <small>Your card will be charged <strong>$<?= number_format($total,2) ?></strong> upon order placement.</small>
                    </div>

                    <!-- Monnify info -->
                    <div id="monnify-info" style="display:none; padding:20px; background:#fff8f8; border:1.5px dashed #ff523b; border-radius:8px; color:#555;">
                        <i class="fa fa-credit-card" style="color:#ff523b; margin-right:8px;"></i>
                        <strong>Moniepoint Payment</strong> — You will be redirected to Monnify's secure payment page where you can pay via:
                        <ul style="margin:10px 0 0 20px; font-size:14px;">
                            <li>💳 Debit/Credit Card</li>
                            <li>🏦 Bank Transfer</li>
                            <li>📱 USSD</li>
                        </ul>
                    </div>
                    <div id="flutterwave-info" style="display:none; padding:20px; background:#fff8f8; border:1.5px dashed #ff523b; border-radius:8px; color:#555;">
                        <i class="fa fa-credit-card"></i> You will be redirected to Flutterwave's secure payment page to complete your payment.
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    <?php foreach ($cart as $item): ?>
                    <div class="order-item">
                        <img src="<?= BASE_URL ?>/images/<?= h($item['image']) ?>" alt="<?= h($item['name']) ?>" style="width:60px; height:60px; object-fit:cover; border-radius:5px;">
                        <div class="order-item-info" style="flex:1;">
                            <p><?= h($item['name']) ?></p>
                            <small>Qty: <?= $item['quantity'] ?> × $<?= number_format($item['price'],2) ?></small>
                        </div>
                        <strong>$<?= number_format($item['price'] * $item['quantity'],2) ?></strong>
                    </div>
                    <?php endforeach; ?>

                    <div class="order-total-row" style="margin-top:15px;">
                        <span>Subtotal</span>
                        <span>$<?= number_format($subtotal,2) ?></span>
                    </div>
                    <div class="order-total-row">
                        <span>Tax (7%)</span>
                        <span>$<?= number_format($tax,2) ?></span>
                    </div>
                    <div class="order-total-row grand-total">
                        <span>Total</span>
                        <span>$<?= number_format($total,2) ?></span>
                    </div>

                    <button type="submit" name="place_order" class="btn" style="width:100%; margin-top:20px; padding:14px; font-size:16px;">
                        Place Order
                    </button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<!-- JavaScript for payment method toggle and Monnify redirect -->
<script>
// ======================================================================
//  SINGLE, CONSOLIDATED PAYMENT METHOD TOGGLE & REDIRECT LOGIC
// ======================================================================

// Get references once (no duplicate const declarations)
const form = document.getElementById('checkoutForm');
const codInfo          = document.getElementById('cod-info');
const stripeSection    = document.getElementById('stripe-section');
const monnifyInfo      = document.getElementById('monnify-info');
const flutterwaveInfo  = document.getElementById('flutterwave-info');
const submitBtn        = form.querySelector('button[type="submit"]');

// ---------- TOGGLE VISIBILITY ----------
function togglePaymentMethod() {
    const method = document.querySelector('input[name="payment_method"]:checked').value;

    // Hide all info panels first
    codInfo.style.display         = 'none';
    if (stripeSection) stripeSection.style.display = 'none';
    if (monnifyInfo) monnifyInfo.style.display     = 'none';
    if (flutterwaveInfo) flutterwaveInfo.style.display = 'none';

    // Show the correct one
    if (method === 'cod') {
        codInfo.style.display = 'block';
    } else if (method === 'stripe') {
        if (stripeSection) stripeSection.style.display = 'block';
    } else if (method === 'monnify') {
        if (monnifyInfo) monnifyInfo.style.display = 'block';
    } else if (method === 'flutterwave') {
        if (flutterwaveInfo) flutterwaveInfo.style.display = 'block';
    }
}

// Attach toggle to all radio buttons
document.querySelectorAll('input[name="payment_method"]').forEach(el => {
    el.addEventListener('change', togglePaymentMethod);
});

// Run once on page load
togglePaymentMethod();

// ---------- STRIPE (optional) ----------
<?php if (defined('STRIPE_PUBLISHABLE_KEY') && STRIPE_PUBLISHABLE_KEY !== ''): ?>
const stripe = Stripe('<?= STRIPE_PUBLISHABLE_KEY ?>');
const elements = stripe.elements();
const cardElement = elements.create('card', { style: { base: { fontSize: '16px' } } });
let cardMounted = false;

document.querySelectorAll('input[name="payment_method"]').forEach(el => {
    el.addEventListener('change', function() {
        if (this.value === 'stripe' && !cardMounted && stripeSection) {
            cardElement.mount('#card-element');
            cardMounted = true;
        }
    });
});
<?php endif; ?>

// ---------- HANDLE FORM SUBMIT (Monnify / Flutterwave) ----------
form.addEventListener('submit', async function(e) {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;

    // Only intercept for Monnify or Flutterwave
    if (paymentMethod !== 'monnify' && paymentMethod !== 'flutterwave') {
        return; // let COD/Stripe submit normally
    }

    e.preventDefault();

    // Validate required fields
    const name  = form.querySelector('input[name="name"]').value.trim();
    const email = form.querySelector('input[name="email"]').value.trim();
    const addr  = form.querySelector('input[name="address"]').value.trim();
    const city  = form.querySelector('input[name="city"]').value.trim();

    if (!name || !email || !addr || !city) {
        showError('Please fill in all shipping fields (name, email, address, city).');
        return;
    }

    // Determine which API to call
    const endpoint = paymentMethod === 'monnify'
        ? '<?= BASE_URL ?>/api/payments/create-monnify-transaction.php'
        : '<?= BASE_URL ?>/api/payments/create-flutterwave-transaction.php';

    const redirectLabel = paymentMethod === 'monnify' ? 'Monnify' : 'Flutterwave';
    submitBtn.disabled = true;
    submitBtn.textContent = 'Redirecting to ' + redirectLabel + '...';

    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name, email: email })
        });
        const data = await response.json();

        if (data.error) {
            showError(data.error + (data.details ? ' (' + data.details + ')' : ''));
        } else if (data.checkoutUrl) {
            window.location.href = data.checkoutUrl;
        } else {
            showError('No checkout URL returned. Please try again.');
        }
    } catch (err) {
        showError('Payment request failed: ' + err.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Place Order';
    }
});

// ---------- ERROR HELPER ----------
function showError(msg) {
    const old = document.getElementById('payment-error');
    if (old) old.remove();
    const div = document.createElement('div');
    div.id = 'payment-error';
    div.className = 'alert alert-error';
    div.textContent = msg;
    const container = document.querySelector('.checkout-grid') || form;
    if (container) container.prepend(div);
}
</script>
</body>
</html>