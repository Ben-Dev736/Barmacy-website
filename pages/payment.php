<?php
session_start();

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Email configuration (for sending confirmation emails)
// In production, use PHPMailer or your SMTP settings
$admin_email = "care@bermacy.rw";
$company_name = "Bermacy RW";

// Get cart data from POST (sent from login.html)
$cart_data = isset($_POST['cart_data']) ? json_decode($_POST['cart_data'], true) : [];
$user_data = isset($_POST['user_data']) ? json_decode($_POST['user_data'], true) : [];
$total_amount = isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : 0;

// If no POST data, try to get from session (fallback)
if(empty($cart_data) && isset($_SESSION['pending_cart'])) {
    $cart_data = $_SESSION['pending_cart'];
    $user_data = $_SESSION['pending_user'];
    $total_amount = $_SESSION['pending_total'];
}

// If still no data, redirect back to shop
if(empty($cart_data)) {
    header("Location: Shop.html");
    exit();
}

// Store in session for persistence
$_SESSION['pending_cart'] = $cart_data;
$_SESSION['pending_user'] = $user_data;
$_SESSION['pending_total'] = $total_amount;

// Product database for price reference
$products_db = [
    1 => ["name" => "Paracetamol 500mg", "price" => 1200],
    2 => ["name" => "Ibuprofen 400mg", "price" => 2500],
    3 => ["name" => "Amoxicillin 500mg", "price" => 3800],
    4 => ["name" => "Vitamin C + Zinc", "price" => 5400],
    5 => ["name" => "Omeprazole 20mg", "price" => 3600],
    6 => ["name" => "Cetirizine 10mg", "price" => 1900],
    7 => ["name" => "Azithromycin 500mg", "price" => 5200],
    8 => ["name" => "Multivitamin Daily", "price" => 8900],
    9 => ["name" => "Loperamide 2mg", "price" => 2100]
];

// Function to send email confirmation
function sendPaymentConfirmation($email, $name, $amount, $payment_method, $cart_items, $transaction_id) {
    $subject = "Payment Confirmation - Bermacy RW Order #" . $transaction_id;
    
    // Build cart items HTML
    $items_html = "";
    foreach($cart_items as $item) {
        global $products_db;
        $product = $products_db[$item['id']] ?? null;
        if($product) {
            $items_html .= "<tr>
                <td style='padding:8px; border-bottom:1px solid #ddd;'>{$product['name']}</td>
                <td style='padding:8px; border-bottom:1px solid #ddd; text-align:center;'>{$item['quantity']}</td>
                <td style='padding:8px; border-bottom:1px solid #ddd; text-align:right;'>" . number_format($product['price'] * $item['quantity'], 0) . " RWF</td>
            </tr>";
        }
    }
    
    $message = "
    <html>
    <head>
        <title>Payment Confirmation - Bermacy RW</title>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f9fc; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 20px; padding: 30px; }
            .header { background: #0b3b3f; color: white; padding: 20px; text-align: center; border-radius: 15px 15px 0 0; }
            .content { padding: 20px; }
            .total { font-size: 24px; font-weight: bold; color: #156f68; text-align: right; margin-top: 15px; padding-top: 10px; border-top: 2px solid #e0ecea; }
            .footer { text-align: center; padding-top: 20px; font-size: 12px; color: #6c8b89; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>BERMACY RW</h2>
                <p>Payment Confirmation</p>
            </div>
            <div class='content'>
                <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
                <p>Thank you for your order! Your payment has been successfully processed.</p>
                
                <h3>Order Details</h3>
                <p><strong>Transaction ID:</strong> " . $transaction_id . "</p>
                <p><strong>Payment Method:</strong> " . htmlspecialchars($payment_method) . "</p>
                <p><strong>Date:</strong> " . date('F j, Y, g:i a') . "</p>
                
                <h3>Items Ordered</h3>
                <table>
                    <tr style='background:#eef3f2;'>
                        <th>Medicine</th>
                        <th>Qty</th>
                        <th>Total</th>
                    </tr>
                    " . $items_html . "
                </table>
                
                <div class='total'>
                    Total Amount: " . number_format($amount, 0) . " RWF
                </div>
                
                <p>Your medicines will be delivered to your registered address within 2-3 business days.</p>
                <p>For any inquiries, please contact us at care@bermacy.rw or call +250 788 123 456.</p>
            </div>
            <div class='footer'>
                <p>© 2025 Bermacy RW - Your Trusted Pharmacy Partner in Rwanda</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Bermacy RW <care@bermacy.rw>" . "\r\n";
    
    // In production, you would use a real SMTP server
    // For demo, we'll simulate sending and store in session
    $_SESSION['last_email'] = [
        'to' => $email,
        'subject' => $subject,
        'message' => $message
    ];
    
    // Actually send email (uncomment in production with proper SMTP)
    // mail($email, $subject, $message, $headers);
    
    return true;
}

// Process payment based on method
$payment_status = "";
$error_message = "";
$transaction_id = "BER" . time() . rand(1000, 9999);

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['payment_action'])) {
    $payment_method = $_POST['payment_method'] ?? '';
    $payment_number = $_POST['payment_number'] ?? '';
    $card_number = $_POST['card_number'] ?? '';
    $card_expiry = $_POST['card_expiry'] ?? '';
    $card_cvv = $_POST['card_cvv'] ?? '';
    $paypal_email = $_POST['paypal_email'] ?? '';
    
    $user_name = $user_data['name'] ?? 'Customer';
    $user_email = $user_data['email'] ?? '';
    
    if($payment_method == 'mtn') {
        // Validate MTN number (078xxxxxxx or 079xxxxxxx, 10 digits total)
        if(preg_match('/^(078|079)[0-9]{7}$/', $payment_number)) {
            $payment_status = "success";
            sendPaymentConfirmation($user_email, $user_name, $total_amount, "MTN Mobile Money", $cart_data, $transaction_id);
            // Clear session cart after successful payment
            unset($_SESSION['pending_cart']);
            unset($_SESSION['pending_user']);
            unset($_SESSION['pending_total']);
        } else {
            $error_message = "Invalid MTN number! Must start with 078 or 079 and be 10 digits total.";
        }
    } 
    elseif($payment_method == 'airtel') {
        // Validate Airtel number (072xxxxxxx or 073xxxxxxx, 10 digits total)
        if(preg_match('/^(072|073)[0-9]{7}$/', $payment_number)) {
            $payment_status = "success";
            sendPaymentConfirmation($user_email, $user_name, $total_amount, "Airtel Money", $cart_data, $transaction_id);
            unset($_SESSION['pending_cart']);
            unset($_SESSION['pending_user']);
            unset($_SESSION['pending_total']);
        } else {
            $error_message = "Invalid Airtel number! Must start with 072 or 073 and be 10 digits total.";
        }
    }
    elseif($payment_method == 'card') {
        // Validate card number (basic validation for demo)
        $card_type = "";
        if(preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/', $card_number)) {
            $card_type = "Visa";
        } elseif(preg_match('/^5[1-5][0-9]{14}$/', $card_number)) {
            $card_type = "Mastercard";
        } elseif(preg_match('/^[0-9]{16}$/', $card_number)) {
            $card_type = "Card";
        } else {
            $error_message = "Invalid card number! Please enter a valid 16-digit card number.";
        }
        
        if(empty($error_message) && !empty($card_expiry) && !empty($card_cvv)) {
            $payment_status = "success";
            sendPaymentConfirmation($user_email, $user_name, $total_amount, $card_type . " Card", $cart_data, $transaction_id);
            unset($_SESSION['pending_cart']);
            unset($_SESSION['pending_user']);
            unset($_SESSION['pending_total']);
        } elseif(empty($error_message)) {
            $error_message = "Please fill in all card details (expiry date and CVV).";
        }
    }
    elseif($payment_method == 'paypal') {
        // Validate PayPal email
        if(filter_var($paypal_email, FILTER_VALIDATE_EMAIL)) {
            $payment_status = "success";
            sendPaymentConfirmation($user_email, $user_name, $total_amount, "PayPal", $cart_data, $transaction_id);
            unset($_SESSION['pending_cart']);
            unset($_SESSION['pending_user']);
            unset($_SESSION['pending_total']);
        } else {
            $error_message = "Invalid PayPal email address!";
        }
    }
}

// If payment was successful, show success page
if($payment_status == "success") {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Successful - Bermacy RW</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
            body { background: linear-gradient(135deg, #d9f0ec, #c1e4df); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
            .success-card { background: white; border-radius: 48px; max-width: 500px; width: 100%; padding: 45px 40px; text-align: center; box-shadow: 0 25px 50px rgba(0,0,0,0.15); }
            .success-icon { width: 80px; height: 80px; background: #d4edda; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; }
            .success-icon i { font-size: 45px; color: #155724; }
            h2 { color: #0a3b3a; margin-bottom: 15px; }
            .transaction-id { background: #eef3f2; padding: 12px; border-radius: 30px; margin: 20px 0; font-family: monospace; }
            .btn { display: inline-block; background: #0b5e5a; color: white; padding: 14px 35px; border-radius: 40px; text-decoration: none; font-weight: 600; margin-top: 20px; transition: 0.2s; }
            .btn:hover { background: #094e4a; transform: translateY(-2px); }
        </style>
    </head>
    <body>
        <div class="success-card">
            <div class="success-icon"><i class="fas fa-check-circle"></i></div>
            <h2>Payment Successful! 🎉</h2>
            <p>Your order has been confirmed and is being processed.</p>
            <div class="transaction-id">Transaction ID: <?php echo $transaction_id; ?></div>
            <p>A confirmation email has been sent to your registered email address.</p>
            <a href="../index.html" class="btn"><i class="fas fa-home"></i> Return to Home</a>
        </div>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    </body>
    </html>
    <?php
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Payment - Bermacy RW | Secure Checkout</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #d9f0ec, #c1e4df); min-height: 100vh; padding: 30px 5%; }
        
        .payment-container { max-width: 1300px; margin: 0 auto; display: flex; gap: 40px; flex-wrap: wrap; }
        .order-summary { flex: 1; background: white; border-radius: 32px; padding: 30px; box-shadow: 0 20px 35px rgba(0,0,0,0.1); height: fit-content; }
        .payment-section { flex: 1.5; background: white; border-radius: 32px; padding: 30px; box-shadow: 0 20px 35px rgba(0,0,0,0.1); }
        
        h2 { color: #0a3b3a; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .cart-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e0ecea; }
        .total-row { display: flex; justify-content: space-between; padding: 15px 0; font-weight: 700; font-size: 1.2rem; border-top: 2px solid #e0ecea; margin-top: 15px; }
        
        .payment-methods { display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap; }
        .method-btn { flex: 1; padding: 15px; border: 2px solid #e0ecea; background: white; border-radius: 60px; cursor: pointer; font-weight: 600; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .method-btn.active { border-color: #2c9c8f; background: #eef3f2; color: #0b5e5a; }
        .method-btn:hover { border-color: #2c9c8f; }
        
        .payment-form { display: none; margin-top: 20px; }
        .payment-form.active { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; font-weight: 600; color: #1f5e5a; margin-bottom: 8px; }
        .input-group input { width: 100%; padding: 14px 18px; border: 1.5px solid #e0ecea; border-radius: 30px; font-size: 1rem; transition: 0.2s; outline: none; }
        .input-group input:focus { border-color: #2c9c8f; box-shadow: 0 0 0 3px rgba(44,156,143,0.1); }
        
        .flash-message { background: #eef3f2; padding: 12px 18px; border-radius: 30px; margin: 15px 0; font-size: 0.85rem; display: none; align-items: center; gap: 10px; }
        .flash-message i { font-size: 1.1rem; }
        .flash-message.mtn { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
        .flash-message.airtel { background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8; }
        .flash-message.visa { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .flash-message.mastercard { background: #cce5ff; color: #004085; border-left: 4px solid #007bff; }
        .flash-message.paypal { background: #e2f0ff; color: #003087; border-left: 4px solid #003087; }
        
        .verify-btn { background: #2c9c8f; color: white; border: none; padding: 14px 28px; border-radius: 40px; font-weight: 600; cursor: pointer; margin-right: 15px; transition: 0.2s; }
        .verify-btn:hover { background: #247a70; }
        .confirm-btn { background: #0b5e5a; color: white; border: none; padding: 14px 28px; border-radius: 40px; font-weight: 600; cursor: pointer; transition: 0.2s; display: none; }
        .confirm-btn.show { display: inline-block; }
        .confirm-btn:hover { background: #094e4a; }
        .error-msg { color: #c62828; font-size: 0.8rem; margin-top: 10px; display: none; }
        .row-2cols { display: flex; gap: 15px; }
        .row-2cols .input-group { flex: 1; }
        
        @media (max-width: 900px) { .payment-container { flex-direction: column; } }
    </style>
</head>
<body>

<div class="payment-container">
    <!-- Order Summary -->
    <div class="order-summary">
        <h2><i class="fas fa-shopping-bag"></i> Order Summary</h2>
        <div id="cartItemsList">
            <?php 
            $subtotal = 0;
            foreach($cart_data as $item): 
                $product = $products_db[$item['id']] ?? null;
                if($product):
                    $item_total = $product['price'] * $item['quantity'];
                    $subtotal += $item_total;
            ?>
            <div class="cart-item">
                <span><strong><?php echo htmlspecialchars($product['name']); ?></strong> x <?php echo $item['quantity']; ?></span>
                <span><?php echo number_format($item_total, 0); ?> RWF</span>
            </div>
            <?php endif; endforeach; ?>
            <div class="total-row">
                <span>Total Amount</span>
                <span style="color:#156f68; font-size:1.4rem;"><?php echo number_format($total_amount, 0); ?> RWF</span>
            </div>
        </div>
        <div style="margin-top: 20px; padding: 15px; background: #eef3f2; border-radius: 20px;">
            <p><i class="fas fa-user"></i> <strong>Customer:</strong> <?php echo htmlspecialchars($user_data['name'] ?? 'Guest'); ?></p>
            <p><i class="fas fa-envelope"></i> <strong>Email:</strong> <?php echo htmlspecialchars($user_data['email'] ?? 'Not provided'); ?></p>
        </div>
    </div>

    <!-- Payment Section -->
    <div class="payment-section">
        <h2><i class="fas fa-credit-card"></i> Select Payment Method</h2>
        
        <!-- Payment Method Tabs -->
        <div class="payment-methods">
            <button class="method-btn" data-method="mtn"><i class="fas fa-mobile-alt"></i> MTN MoMo</button>
            <button class="method-btn" data-method="airtel"><i class="fas fa-mobile-alt"></i> Airtel Money</button>
            <button class="method-btn" data-method="card"><i class="fas fa-credit-card"></i> Card</button>
            <button class="method-btn" data-method="paypal"><i class="fab fa-paypal"></i> PayPal</button>
        </div>
        
        <!-- MTN Form -->
        <div id="mtnForm" class="payment-form">
            <div class="input-group">
                <label><i class="fas fa-phone"></i> MTN Mobile Number</label>
                <input type="tel" id="mtnNumber" placeholder="078XXXXXXX or 079XXXXXXX" maxlength="10">
                <div id="mtnFlash" class="flash-message"></div>
            </div>
            <button class="verify-btn" onclick="verifyMtn()">Verify Number</button>
            <button id="confirmMtnBtn" class="confirm-btn" onclick="submitPayment('mtn')">Confirm Payment</button>
            <div id="mtnError" class="error-msg"></div>
        </div>
        
        <!-- Airtel Form -->
        <div id="airtelForm" class="payment-form">
            <div class="input-group">
                <label><i class="fas fa-phone"></i> Airtel Mobile Number</label>
                <input type="tel" id="airtelNumber" placeholder="072XXXXXXX or 073XXXXXXX" maxlength="10">
                <div id="airtelFlash" class="flash-message"></div>
            </div>
            <button class="verify-btn" onclick="verifyAirtel()">Verify Number</button>
            <button id="confirmAirtelBtn" class="confirm-btn" onclick="submitPayment('airtel')">Confirm Payment</button>
            <div id="airtelError" class="error-msg"></div>
        </div>
        
        <!-- Card Form -->
        <div id="cardForm" class="payment-form">
            <div class="input-group">
                <label><i class="fas fa-credit-card"></i> Card Number</label>
                <input type="text" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19">
                <div id="cardFlash" class="flash-message"></div>
            </div>
            <div class="row-2cols">
                <div class="input-group">
                    <label>Expiry Date</label>
                    <input type="text" id="cardExpiry" placeholder="MM/YY">
                </div>
                <div class="input-group">
                    <label>CVV</label>
                    <input type="password" id="cardCvv" placeholder="123" maxlength="4">
                </div>
            </div>
            <button class="verify-btn" onclick="verifyCard()">Verify Card</button>
            <button id="confirmCardBtn" class="confirm-btn" onclick="submitPayment('card')">Confirm Payment</button>
            <div id="cardError" class="error-msg"></div>
        </div>
        
        <!-- PayPal Form -->
        <div id="paypalForm" class="payment-form">
            <div class="input-group">
                <label><i class="fab fa-paypal"></i> PayPal Email</label>
                <input type="email" id="paypalEmail" placeholder="your@email.com">
                <div id="paypalFlash" class="flash-message"></div>
            </div>
            <button class="verify-btn" onclick="verifyPaypal()">Verify Email</button>
            <button id="confirmPaypalBtn" class="confirm-btn" onclick="submitPayment('paypal')">Confirm Payment</button>
            <div id="paypalError" class="error-msg"></div>
        </div>
        
        <!-- Warning Message -->
        <div id="warningMsg" style="margin-top: 20px; padding: 12px; background: #fff3cd; border-radius: 30px; display: none; font-size: 0.8rem;">
            <i class="fas fa-exclamation-triangle"></i> Warning: Please complete your payment. Do not navigate back without confirming payment.
        </div>
    </div>
</div>

<form id="paymentForm" method="POST" style="display: none;">
    <input type="hidden" name="payment_action" value="1">
    <input type="hidden" name="payment_method" id="hiddenMethod">
    <input type="hidden" name="payment_number" id="hiddenNumber">
    <input type="hidden" name="card_number" id="hiddenCardNumber">
    <input type="hidden" name="card_expiry" id="hiddenCardExpiry">
    <input type="hidden" name="card_cvv" id="hiddenCardCvv">
    <input type="hidden" name="paypal_email" id="hiddenPaypalEmail">
</form>

<script>
    // Tab switching
    let currentMethod = 'mtn';
    let verified = { mtn: false, airtel: false, card: false, paypal: false };
    let verifiedValue = { mtn: '', airtel: '', card: '', paypal: '' };
    
    document.querySelectorAll('.method-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.method-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentMethod = btn.dataset.method;
            document.querySelectorAll('.payment-form').forEach(form => form.classList.remove('active'));
            document.getElementById(`${currentMethod}Form`).classList.add('active');
            
            // Reset warning
            document.getElementById('warningMsg').style.display = 'none';
        });
    });
    
    // MTN Verification - detects network on first 3 digits
    const mtnInput = document.getElementById('mtnNumber');
    const mtnFlash = document.getElementById('mtnFlash');
    
    mtnInput.addEventListener('input', function() {
        let val = this.value.replace(/\D/g, '').substring(0, 10);
        this.value = val;
        
        if(val.length >= 3) {
            let prefix = val.substring(0, 3);
            if(prefix === '078' || prefix === '079') {
                mtnFlash.innerHTML = '<i class="fas fa-check-circle"></i> ✓ MTN Rwanda number detected';
                mtnFlash.className = 'flash-message mtn';
                mtnFlash.style.display = 'flex';
            } else {
                mtnFlash.innerHTML = '<i class="fas fa-exclamation-circle"></i> ⚠️ Invalid MTN prefix. Should start with 078 or 079';
                mtnFlash.className = 'flash-message';
                mtnFlash.style.display = 'flex';
            }
        } else {
            mtnFlash.style.display = 'none';
        }
    });
    
    function verifyMtn() {
        let number = document.getElementById('mtnNumber').value;
        let errorDiv = document.getElementById('mtnError');
        let confirmBtn = document.getElementById('confirmMtnBtn');
        
        if(number.length !== 10) {
            errorDiv.textContent = 'Invalid MTN number! Must be exactly 10 digits.';
            errorDiv.style.display = 'block';
            confirmBtn.classList.remove('show');
            verified.mtn = false;
            return;
        }
        
        if(!/^(078|079)[0-9]{7}$/.test(number)) {
            errorDiv.textContent = 'Invalid MTN number! Must start with 078 or 079.';
            errorDiv.style.display = 'block';
            confirmBtn.classList.remove('show');
            verified.mtn = false;
            return;
        }
        
        errorDiv.style.display = 'none';
        verified.mtn = true;
        verifiedValue.mtn = number;
        confirmBtn.classList.add('show');
        mtnFlash.innerHTML = '<i class="fas fa-check-circle"></i> ✓ MTN number verified! Click confirm to proceed.';
        mtnFlash.className = 'flash-message mtn';
        mtnFlash.style.display = 'flex';
        document.getElementById('warningMsg').style.display = 'block';
    }
    
    // Airtel Verification
    const airtelInput = document.getElementById('airtelNumber');
    const airtelFlash = document.getElementById('airtelFlash');
    
    airtelInput.addEventListener('input', function() {
        let val = this.value.replace(/\D/g, '').substring(0, 10);
        this.value = val;
        
        if(val.length >= 3) {
            let prefix = val.substring(0, 3);
            if(prefix === '072' || prefix === '073') {
                airtelFlash.innerHTML = '<i class="fas fa-check-circle"></i> ✓ Airtel Rwanda number detected';
                airtelFlash.className = 'flash-message airtel';
                airtelFlash.style.display = 'flex';
            } else {
                airtelFlash.innerHTML = '<i class="fas fa-exclamation-circle"></i> ⚠️ Invalid Airtel prefix. Should start with 072 or 073';
                airtelFlash.className = 'flash-message';
                airtelFlash.style.display = 'flex';
            }
        } else {
            airtelFlash.style.display = 'none';
        }
    });
    
    function verifyAirtel() {
        let number = document.getElementById('airtelNumber').value;
        let errorDiv = document.getElementById('airtelError');
        let confirmBtn = document.getElementById('confirmAirtelBtn');
        
        if(number.length !== 10) {
            errorDiv.textContent = 'Invalid Airtel number! Must be exactly 10 digits.';
            errorDiv.style.display = 'block';
            confirmBtn.classList.remove('show');
            verified.airtel = false;
            return;
        }
        
        if(!/^(072|073)[0-9]{7}$/.test(number)) {
            errorDiv.textContent = 'Invalid Airtel number! Must start with 072 or 073.';
            errorDiv.style.display = 'block';
            confirmBtn.classList.remove('show');
            verified.airtel = false;
            return;
        }
        
        errorDiv.style.display = 'none';
        verified.airtel = true;
        verifiedValue.airtel = number;
        confirmBtn.classList.add('show');
        airtelFlash.innerHTML = '<i class="fas fa-check-circle"></i> ✓ Airtel number verified! Click confirm to proceed.';
        airtelFlash.className = 'flash-message airtel';
        airtelFlash.style.display = 'flex';
        document.getElementById('warningMsg').style.display = 'block';
    }
    
    // Card Verification
    const cardInput = document.getElementById('cardNumber');
    const cardFlash = document.getElementById('cardFlash');
    
    cardInput.addEventListener('input', function() {
        let val = this.value.replace(/\D/g, '').substring(0, 16);
        let formatted = val.replace(/(\d{4})/g, '$1 ').trim();
        this.value = formatted;
        
        if(val.length >= 4) {
            let firstDigit = val.charAt(0);
            if(firstDigit === '4') {
                cardFlash.innerHTML = '<i class="fab fa-cc-visa"></i> ✓ Visa card detected';
                cardFlash.className = 'flash-message visa';
                cardFlash.style.display = 'flex';
            } else if(firstDigit === '5') {
                cardFlash.innerHTML = '<i class="fab fa-cc-mastercard"></i> ✓ Mastercard detected';
                cardFlash.className = 'flash-message mastercard';
                cardFlash.style.display = 'flex';
            } else {
                cardFlash.innerHTML = '<i class="fas fa-credit-card"></i> Card detected';
                cardFlash.className = 'flash-message';
                cardFlash.style.display = 'flex';
            }
        } else {
            cardFlash.style.display = 'none';
        }
    });
    
    function verifyCard() {
        let cardNumber = document.getElementById('cardNumber').value.replace(/\D/g, '');
        let expiry = document.getElementById('cardExpiry').value;
        let cvv = document.getElementById('cardCvv').value;
        let errorDiv = document.getElementById('cardError');
        let confirmBtn = document.getElementById('confirmCardBtn');
        
        if(cardNumber.length < 15 || cardNumber.length > 16) {
            errorDiv.textContent = 'Invalid card number! Please enter a valid 15-16 digit card number.';
            errorDiv.style.display = 'block';
            confirmBtn.classList.remove('show');
            verified.card = false;
            return;
        }
        
        if(!expiry.match(/^(0[1-9]|1[0-2])\/([0-9]{2})$/)) {
            errorDiv.textContent = 'Invalid expiry date! Use MM/YY format.';
            errorDiv.style.display = 'block';
            confirmBtn.classList.remove('show');
            verified.card = false;
            return;
        }
        
        if(cvv.length < 3 || cvv.length > 4) {
            errorDiv.textContent = 'Invalid CVV! Must be 3-4 digits.';
            errorDiv.style.display = 'block';
            confirmBtn.classList.remove('show');
            verified.card = false;
            return;
        }
        
        errorDiv.style.display = 'none';
        verified.card = true;
        verifiedValue.card = cardNumber;
        verifiedValue.cardExpiry = expiry;
        verifiedValue.cardCvv = cvv;
        confirmBtn.classList.add('show');
        cardFlash.innerHTML = '<i class="fas fa-check-circle"></i> ✓ Card verified! Click confirm to complete payment.';
        cardFlash.style.display = 'flex';
        document.getElementById('warningMsg').style.display = 'block';
    }
    
    // PayPal Verification
    function verifyPaypal() {
        let email = document.getElementById('paypalEmail').value;
        let errorDiv = document.getElementById('paypalError');
        let confirmBtn = document.getElementById('confirmPaypalBtn');
        let paypalFlash = document.getElementById('paypalFlash');
        
        if(!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            errorDiv.textContent = 'Invalid email address! Please enter a valid PayPal email.';
            errorDiv.style.display = 'block';
            confirmBtn.classList.remove('show');
            verified.paypal = false;
            return;
        }
        
        errorDiv.style.display = 'none';
        verified.paypal = true;
        verifiedValue.paypal = email;
        confirmBtn.classList.add('show');
        paypalFlash.innerHTML = '<i class="fab fa-paypal"></i> ✓ PayPal account verified! Click confirm to proceed.';
        paypalFlash.className = 'flash-message paypal';
        paypalFlash.style.display = 'flex';
        document.getElementById('warningMsg').style.display = 'block';
    }
    
    // Submit payment
    function submitPayment(method) {
        if(!verified[method]) {
            alert('Please verify your payment details first!');
            return;
        }
        
        let form = document.getElementById('paymentForm');
        let hiddenMethod = document.getElementById('hiddenMethod');
        let hiddenNumber = document.getElementById('hiddenNumber');
        let hiddenCardNumber = document.getElementById('hiddenCardNumber');
        let hiddenCardExpiry = document.getElementById('hiddenCardExpiry');
        let hiddenCardCvv = document.getElementById('hiddenCardCvv');
        let hiddenPaypalEmail = document.getElementById('hiddenPaypalEmail');
        
        hiddenMethod.value = method;
        
        if(method === 'mtn') {
            hiddenNumber.value = verifiedValue.mtn;
        } else if(method === 'airtel') {
            hiddenNumber.value = verifiedValue.airtel;
        } else if(method === 'card') {
            hiddenCardNumber.value = verifiedValue.card;
            hiddenCardExpiry.value = verifiedValue.cardExpiry;
            hiddenCardCvv.value = verifiedValue.cardCvv;
        } else if(method === 'paypal') {
            hiddenPaypalEmail.value = verifiedValue.paypal;
        }
        
        form.submit();
    }
    
    // Warn user before leaving page without payment
    window.addEventListener('beforeunload', function(e) {
        let anyVerified = verified.mtn || verified.airtel || verified.card || verified.paypal;
        if(anyVerified) {
            e.preventDefault();
            e.returnValue = 'You have not completed your payment. Are you sure you want to leave?';
            return 'You have not completed your payment. Are you sure you want to leave?';
        }
    });
    
    // Set default active method
    document.querySelector('.method-btn').classList.add('active');
    document.getElementById('mtnForm').classList.add('active');
</script>
</body>
</html>
