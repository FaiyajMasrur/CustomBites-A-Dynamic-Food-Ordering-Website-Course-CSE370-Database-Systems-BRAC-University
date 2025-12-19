<?php
// Start session
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if cart is empty
if(!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    header("Location: order_food.php");
    exit();
}

// Include database connection
include 'dbconnect.php';

// Initialize variables
$payment_success = false;
$payment_error = "";
$coupon_message = "";
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : "";
$confirmation_message = "";
$show_card_form = ($payment_method == 'Online');

// Calculate cart total
$cart_total = 0;
foreach($_SESSION['cart'] as $item) {
    $cart_total += $item['subtotal'];
}

// Fetch user details
$stmt = $conn->prepare("SELECT u.First_name, c.Credits FROM users u 
                        JOIN customers c ON u.User_ID = c.Customer_ID 
                        WHERE u.User_ID = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$first_name = $user['First_name'];
$user_credits = $user['Credits'];
$stmt->close();

// Handle coupon check
if(isset($_POST['check_coupon'])) {
    $coupon_message = "No coupons available at the moment";
}

// Handle payment form submission
if(isset($_POST['process_payment'])) {
    $payment_method = $_POST['payment_method'];
    
    if(empty($payment_method)) {
        $payment_error = "Please select a payment method";
    } else {
        $order_date = date('Y-m-d H:i:s');
        $user_id = $_SESSION['user_id'];
        
        // Process based on payment method
        switch($payment_method) {
            case 'CustomPay':
                if($user_credits < $cart_total) {
                    $payment_error = "Not enough credits. You have: $" . number_format($user_credits, 2);
                    break;
                }
                
                // Deduct credits
                $new_credits = $user_credits - $cart_total;
                $stmt = $conn->prepare("UPDATE customers SET Credits = ? WHERE Customer_ID = ?");
                $stmt->bind_param("di", $new_credits, $user_id);
                $stmt->execute();
                $stmt->close();
                
                $confirmation_message = "You have used your credits. Our rider will be at your doorstep shortly.";
                $payment_success = true;
                break;
                
            case 'COD':
                $confirmation_message = "Our rider will be at your doorstep shortly. Please pay $" . number_format($cart_total, 2) . " to our rider";
                $payment_success = true;
                break;
                
            case 'Online':
                // Simulate online payment (in real-world, this would connect to a payment gateway)
                if(isset($_POST['card_number']) && isset($_POST['card_name']) && isset($_POST['expiry_date']) && isset($_POST['cvv'])) {
                    $card_number = $_POST['card_number'];
                    $card_name = $_POST['card_name'];
                    $expiry_date = $_POST['expiry_date'];
                    $cvv = $_POST['cvv'];
                    
                    // Simple validation
                    if(empty($card_number) || empty($card_name) || empty($expiry_date) || empty($cvv)) {
                        $payment_error = "All card fields are required";
                    } elseif(strlen($card_number) != 16 || !is_numeric($card_number)) {
                        $payment_error = "Invalid card number";
                    } elseif(strlen($cvv) != 3 || !is_numeric($cvv)) {
                        $payment_error = "Invalid CVV";
                    } else {
                        $confirmation_message = "You have paid $" . number_format($cart_total, 2) . ". Our rider will be at your doorstep shortly.";
                        $payment_success = true;
                    }
                } else {
                    $payment_error = "Card details required for online payment";
                }
                break;
        }
        
        // If payment is successful, create order
        if($payment_success) {
            // Create a new order
            $stmt = $conn->prepare("INSERT INTO order_details (Subtotal, Status) VALUES (?, 'paid')");
            $stmt->bind_param("d", $cart_total);
            $stmt->execute();
            $order_id = $stmt->insert_id;
            $stmt->close();
            
            // Create order items
            foreach($_SESSION['cart'] as $item) {
                // Insert into order_details_items
                $stmt = $conn->prepare("INSERT INTO order_details_items (Item_Name, Quantity, Price, Order_ID) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sidd", $item['name'], $item['quantity'], $item['subtotal'], $order_id);
                $stmt->execute();
                $stmt->close();
                
                // Insert customizations if any
                if(!empty($item['customizations'])) {
                    $stmt = $conn->prepare("INSERT INTO order_details_customization (Item_Name, Customizations, Order_ID) VALUES (?, ?, ?)");
                    $stmt->bind_param("ssi", $item['name'], $item['customizations'], $order_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            
            // Insert into orders table
            $stmt = $conn->prepare("INSERT INTO orders (Customer_ID) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            // Record payment
            $payment_date = date('Y-m-d');
            $items_summary = implode(", ", array_map(function($item) {
                return $item['name'] . " x" . $item['quantity'];
            }, $_SESSION['cart']));
            
            $stmt = $conn->prepare("INSERT INTO payment (Customer_ID, Method, Item, Date) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $payment_method, $items_summary, $payment_date);
            $stmt->execute();
            $stmt->close();
            
            // Clear the cart
            $_SESSION['cart'] = array();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Bites - Payment</title>
    <style>
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header Styles */
        header {
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 40px;
            margin-right: 10px;
        }
        
        .logo h1 {
            font-size: 22px;
            margin: 0;
            color: #ff5722;
        }
        
        nav ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        nav ul li {
            margin: 0 5px;
        }
        
        nav ul li a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        nav ul li a:hover,
        nav ul li a.active {
            background-color: #ff5722;
            color: #fff;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info span {
            margin-right: 15px;
            font-weight: 500;
        }
        
        .logout-btn {
            background-color: transparent;
            border: 1px solid #ff5722;
            color: #ff5722;
            padding: 5px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            background-color: #ff5722;
            color: #fff;
        }
        
        /* Page Content */
        .page-content {
            padding-top: 100px;
            padding-bottom: 60px;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 30px;
            font-size: 32px;
            color: #333;
        }
        
        /* Payment Section */
        .payment-section {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .cart-summary {
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        
        .cart-summary h3 {
            margin-top: 0;
            color: #333;
        }
        
        .cart-items {
            margin-bottom: 20px;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item-info {
            flex: 1;
        }
        
        .cart-item-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .cart-item-customize {
            font-size: 12px;
            color: #666;
            font-style: italic;
        }
        
        .cart-item-quantity {
            color: #666;
            font-size: 14px;
        }
        
        .cart-item-price {
            font-weight: bold;
            color: #ff5722;
        }
        
        .cart-total {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: bold;
            padding: 10px 0;
        }
        
        .payment-form h3 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .payment-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
        }
        
        .payment-btn:hover {
            background-color: #3e8e41;
        }
        
        .error-message {
            color: #f44336;
            margin-bottom: 20px;
        }
        
        /* Success Message */
        .success-message {
            text-align: center;
            padding: 30px;
        }
        
        .success-icon {
            font-size: 48px;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        
        .success-title {
            font-size: 24px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .success-text {
            margin-bottom: 25px;
            color: #666;
            line-height: 1.5;
        }
        
        .back-btn {
            background-color: #ff5722;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .back-btn:hover {
            background-color: #e64a19;
        }
        
        /* Payment Method Styles */
        .payment-methods {
            margin-bottom: 20px;
        }
        
        .payment-method-option {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }
        
        .payment-method-option:hover {
            border-color: #ff5722;
        }
        
        .payment-method-option.selected {
            border-color: #ff5722;
            background-color: rgba(255, 87, 34, 0.05);
        }
        
        .payment-method-radio {
            margin-right: 10px;
        }
        
        .payment-method-details {
            margin-top: 10px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 4px;
            background-color: #fafafa;
        }
        
        .payment-method-content {
            display: none;
        }
        
        .payment-method-content.active {
            display: block;
        }
        
        /* Coupon Styles */
        .coupon-section {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .coupon-btn {
            background-color: transparent;
            border: 1px solid #ff5722;
            color: #ff5722;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            margin-right: 10px;
        }
        
        .coupon-btn:hover {
            background-color: #ff5722;
            color: #fff;
        }
        
        .coupon-message {
            font-size: 14px;
            color: #666;
            font-style: italic;
        }
        
        /* Credits Display */
        .user-credits {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f0f0f0;
            border-radius: 4px;
            text-align: center;
        }
        
        .user-credits span {
            font-weight: bold;
            color: #ff5722;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <img src="logo.jpeg" alt="Custom Bites Logo" onerror="this.onerror=null; this.src='data:image/svg+xml;charset=utf-8,%3Csvg xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22 width%3D%2240%22 height%3D%2240%22 viewBox%3D%220 0 40 40%22%3E%3Crect width%3D%2240%22 height%3D%2240%22 fill%3D%22%23f0f0f0%22%2F%3E%3Ctext x%3D%2220%22 y%3D%2220%22 font-family%3D%22Arial%22 font-size%3D%2210%22 fill%3D%22%23999%22 text-anchor%3D%22middle%22 dominant-baseline%3D%22middle%22%3ELogo%3C%2Ftext%3E%3C%2Fsvg%3E'">
                <h1>Custom Bites</h1>
            </div>
            
            <nav>
                <ul>
                    <li><a href="homepage.php">Home</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li><a href="order_food.php">Order Food</a></li>
                    <li><a href="customize.php">Total Customization</a></li>
                    <li><a href="reviews.php">Reviews</a></li>
                </ul>
            </nav>
            
            <div class="user-info">
                <span>Hello, <?php echo htmlspecialchars($first_name); ?></span>
                <form action="logout.php" method="post">
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </div>
        </div>
    </header>
    
    <div class="page-content">
        <div class="container">
            <h1 class="page-title">Payment</h1>
            
            <div class="payment-section">
                <?php if($payment_success): ?>
                    <!-- Payment Success Message -->
                    <div class="success-message">
                        <div class="success-icon">✓</div>
                        <h2 class="success-title">Payment Successful!</h2>
                        <p class="success-text"><?php echo $confirmation_message; ?></p>
                        <a href="order_food.php" class="back-btn">Continue Shopping</a>
                    </div>
                <?php else: ?>
                    <!-- Cart Summary -->
                    <div class="cart-summary">
                        <h3>Order Summary</h3>
                        <div class="cart-items">
                            <?php foreach($_SESSION['cart'] as $item): ?>
                                <div class="cart-item">
                                    <div class="cart-item-info">
                                        <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <?php if(!empty($item['customizations'])): ?>
                                            <div class="cart-item-customize">Customizations: <?php echo htmlspecialchars($item['customizations']); ?></div>
                                        <?php endif; ?>
                                        <div class="cart-item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                                    </div>
                                    <div class="cart-item-price">
                                        $<?php echo number_format($item['subtotal'], 2); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="cart-total">
                            <span>Total:</span>
                            <span>$<?php echo number_format($cart_total, 2); ?></span>
                        </div>
                        
                        <!-- User Credits Display -->
                        <div class="user-credits">
                            Your Credits: <span>$<?php echo number_format($user_credits, 2); ?></span>
                        </div>
                        
                        <!-- Coupon Section -->
                        <div class="coupon-section">
                            <form method="post" action="payment.php">
                                <button type="submit" name="check_coupon" class="coupon-btn">Use Coupon</button>
                            </form>
                            <?php if(!empty($coupon_message)): ?>
                                <div class="coupon-message"><?php echo $coupon_message; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Payment Form -->
                    <div class="payment-form">
                        <h3>Choose Payment Method</h3>
                        
                        <?php if(!empty($payment_error)): ?>
                            <div class="error-message"><?php echo $payment_error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" action="payment.php" id="payment-form">
                            <!-- Payment Methods -->
                            <div class="payment-methods">
                                <!-- CustomPay Option -->
                                <div class="payment-method-option <?php echo ($payment_method == 'CustomPay') ? 'selected' : ''; ?>">
                                    <input type="radio" name="payment_method" id="CustomPay" value="CustomPay" 
                                           class="payment-method-radio" <?php echo ($payment_method == 'CustomPay') ? 'checked' : ''; ?>>
                                    <label for="CustomPay">CustomPay (Use Your Credits)</label>
                                </div>
                                
                                <!-- COD Option -->
                                <div class="payment-method-option <?php echo ($payment_method == 'COD') ? 'selected' : ''; ?>">
                                    <input type="radio" name="payment_method" id="COD" value="COD" 
                                           class="payment-method-radio" <?php echo ($payment_method == 'COD') ? 'checked' : ''; ?>>
                                    <label for="COD">Cash On Delivery</label>
                                </div>
                                
                                <!-- Online Payment Option -->
                                <div class="payment-method-option <?php echo ($payment_method == 'Online') ? 'selected' : ''; ?>">
                                    <input type="radio" name="payment_method" id="Online" value="Online" 
                                           class="payment-method-radio" <?php echo ($payment_method == 'Online') ? 'checked' : ''; ?>>
                                    <label for="Online">Online Payment</label>
                                </div>
                                
                                <!-- Online Payment Details Form -->
                                <?php if($payment_method == 'Online'): ?>
                                <div class="payment-method-details">
                                    <div class="form-group">
                                        <label for="card_name">Name on Card</label>
                                        <input type="text" id="card_name" name="card_name">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="card_number">Card Number</label>
                                        <input type="text" id="card_number" name="card_number" maxlength="16" placeholder="1234 5678 9012 3456">
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="expiry_date">Expiry Date</label>
                                            <input type="text" id="expiry_date" name="expiry_date" placeholder="MM/YY">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="cvv">CVV</label>
                                            <input type="text" id="cvv" name="cvv" maxlength="3">
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Change payment method button -->
                            <?php if($payment_method): ?>
                            <button type="submit" name="process_payment" class="payment-btn">Pay Now</button>
                            <?php else: ?>
                            <button type="submit" class="payment-btn">Select Payment Method</button>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    

</body>
</html>