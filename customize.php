<?php
// Start session
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Initialize cart if it doesn't exist
if(!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Include database connection
include 'dbconnect.php';

// Get user's first name
$stmt = $conn->prepare("SELECT First_name FROM users WHERE User_ID = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$first_name = $user['First_name'];
$stmt->close();

// Available food categories with base prices
$food_categories = array(
    "Burger" => array("price" => 700),
    "Pizza" => array("price" => 1000),
    "Pasta" => array("price" => 600),
    "Salad" => array("price" => 500),
    "Cupcake" => array("price" => 400)
);

// Fetch customization options and pricing from database
$customization_data = array();
$stmt = $conn->prepare("SELECT Food_Category, Customization_Type, Ingredient_Name, Additional_Price, Is_Default 
                       FROM customization_ingredients 
                       ORDER BY Food_Category, Customization_Type, Ingredient_Name");
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()) {
    $category = $row['Food_Category'];
    $type = $row['Customization_Type'];
    
    if(!isset($customization_data[$category])) {
        $customization_data[$category] = array();
    }
    
    if(!isset($customization_data[$category][$type])) {
        $customization_data[$category][$type] = array();
    }
    
    $customization_data[$category][$type][] = array(
        'name' => $row['Ingredient_Name'],
        'price' => floatval($row['Additional_Price']),
        'is_default' => $row['Is_Default']
    );
}
$stmt->close();

// Handle Remove from cart
if(isset($_GET['remove']) && isset($_SESSION['cart'][$_GET['remove']])) {
    // Remove from session cart
    unset($_SESSION['cart'][$_GET['remove']]);
    
    header("Location: customize.php?removed=1");
    exit();
}

// Process form submission - Add to Cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_order'])) {
    $food_type = $_POST['food_type'];
    $customizations = array();
    $total_price = $food_categories[$food_type]['price'];
    
    // Collect customization choices and calculate total price
    foreach ($customization_data[$food_type] as $type => $options) {
        if (isset($_POST[$type])) {
            $selected_option = $_POST[$type];
            $customizations[$type] = $selected_option;
            
            // Find the price for selected option
            foreach($options as $option) {
                if($option['name'] == $selected_option) {
                    $total_price += $option['price'];
                    break;
                }
            }
        }
    }
    
    // Create customization text for display
    $customization_text = "";
    foreach ($customizations as $type => $option) {
        $customization_text .= "$type: $option; ";
    }
    $customization_text = rtrim($customization_text, "; ");
    
    // Generate unique cart item ID
    $cart_item_id = 'custom_' . time() . '_' . rand(1000, 9999);
    
    // Add to cart session
    $_SESSION['cart'][$cart_item_id] = array(
        'item_id' => null, // Custom items don't have item_id
        'name' => "Custom " . $food_type,
        'price' => $total_price,
        'quantity' => 1,
        'customizations' => $customization_text,
        'subtotal' => $total_price,
        'is_custom' => true, // Flag to identify custom items
        'food_type' => $food_type
    );
    
    // Redirect to avoid form resubmission
    header("Location: customize.php?added=1&food=" . urlencode($food_type));
    exit();
}

// Default selected food type
$selected_food = isset($_GET['food']) && array_key_exists($_GET['food'], $food_categories) 
    ? $_GET['food'] 
    : key($food_categories);

// Prepare ingredient pricing data as JSON for JavaScript
$ingredient_prices = array();
foreach($customization_data as $category => $types) {
    $ingredient_prices[$category] = array();
    foreach($types as $type => $options) {
        $ingredient_prices[$category][$type] = array();
        foreach($options as $option) {
            $ingredient_prices[$category][$type][$option['name']] = $option['price'];
        }
    }
}

// Calculate cart total
$cart_total = 0;
foreach($_SESSION['cart'] as $item) {
    $cart_total += $item['subtotal'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Bites - Customization</title>
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
        
        /* Main Content */
        .main-content {
            padding-top: 100px;
            padding-bottom: 60px;
        }
        
        .page-title {
            text-align: center;
            font-size: 32px;
            margin-bottom: 30px;
            color: #333;
        }
        
        /* Food Category Tabs */
        .food-tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .food-tab {
            padding: 10px 25px;
            background-color: #f0f0f0;
            border: none;
            margin: 5px;
            cursor: pointer;
            font-size: 16px;
            border-radius: 25px;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
        }
        
        .food-tab.active {
            background-color: #ff5722;
            color: #fff;
        }
        
        /* Customization Form */
        .customization-form {
            background-color: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-title {
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
            color: #ff5722;
        }
        
        .form-section {
            margin-bottom: 25px;
        }
        
        .form-section h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .option-item {
            position: relative;
        }
        
        .option-item input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .option-item label {
            display: block;
            padding: 12px 15px;
            background-color: #f7f7f7;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .option-item input[type="radio"]:checked + label {
            border-color: #ff5722;
            background-color: #fff5f0;
        }
        
        .option-item label:hover {
            border-color: #ff5722;
        }
        
        .option-price {
            display: block;
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .option-price.positive {
            color: #ff5722;
        }
        
        .option-price.negative {
            color: #4caf50;
        }
        
        .submit-section {
            margin-top: 30px;
            text-align: center;
        }
        
        .price-display {
            font-size: 24px;
            margin-bottom: 15px;
            font-weight: bold;
            color: #ff5722;
        }
        
        .price-breakdown {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .submit-btn {
            background-color: #ff5722;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .submit-btn:hover {
            background-color: #e64a19;
            transform: translateY(-2px);
        }
        
        /* Messages */
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Cart Section */
        .cart-section {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 900;
        }
        
        .view-cart-btn {
            background-color: #ff5722;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            cursor: pointer;
            display: flex;
            align-items: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
        }
        
        .view-cart-btn:hover {
            background-color: #e64a19;
            transform: translateY(-2px);
        }
        
        .cart-icon {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .cart-counter {
            background-color: white;
            color: #ff5722;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            margin-left: 5px;
        }
        
        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1001;
            justify-content: center;
            align-items: center;
        }
        
        .modal {
            background-color: white;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            padding: 20px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
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
        
        .cart-item-remove {
            color: #ff5722;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            margin-left: 10px;
        }
        
        .cart-total {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: bold;
            padding: 10px 0;
            border-top: 2px solid #eee;
            margin-top: 10px;
        }
        
        .cart-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .continue-shopping {
            background-color: #999;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .continue-shopping:hover {
            background-color: #777;
        }
        
        .checkout-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .checkout-btn:hover {
            background-color: #3e8e41;
        }
        
        /* Notification */
        .notification {
            position: fixed;
            top: 80px;
            right: 20px;
            background-color: #4CAF50;
            color: white;
            padding: 15px 20px;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            display: none;
            z-index: 1000;
            animation: fadeInOut 3s forwards;
        }
        
        @keyframes fadeInOut {
            0% { opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { opacity: 0; }
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
                    <li><a href="customize.php" class="active">Total Customization</a></li>
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
    
    <div class="main-content">
        <div class="container">
            <h1 class="page-title">Customize Your Food</h1>
            
            <div class="food-tabs">
                <?php foreach($food_categories as $category => $details): ?>
                    <a href="?food=<?php echo urlencode($category); ?>" class="food-tab <?php echo ($selected_food == $category) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($category); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div class="customization-form">
                <h2 class="form-title">Customize Your <?php echo htmlspecialchars($selected_food); ?></h2>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="customizeForm">
                    <input type="hidden" name="food_type" value="<?php echo htmlspecialchars($selected_food); ?>">
                    
                    <?php if(isset($customization_data[$selected_food])): ?>
                        <?php foreach($customization_data[$selected_food] as $type => $options): ?>
                            <div class="form-section">
                                <h3><?php echo htmlspecialchars($type); ?></h3>
                                <div class="options-grid">
                                    <?php foreach($options as $index => $option): ?>
                                        <div class="option-item">
                                            <input type="radio" 
                                                   id="<?php echo htmlspecialchars($type . '_' . $index); ?>" 
                                                   name="<?php echo htmlspecialchars($type); ?>" 
                                                   value="<?php echo htmlspecialchars($option['name']); ?>"
                                                   data-price="<?php echo $option['price']; ?>"
                                                   <?php echo $option['is_default'] ? 'checked' : ''; ?>
                                                   class="ingredient-option">
                                            <label for="<?php echo htmlspecialchars($type . '_' . $index); ?>">
                                                <?php echo htmlspecialchars($option['name']); ?>
                                                <?php if($option['price'] != 0): ?>
                                                    <span class="option-price <?php echo $option['price'] > 0 ? 'positive' : 'negative'; ?>">
                                                        <?php echo $option['price'] > 0 ? '+' : ''; ?><?php echo number_format($option['price'], 2); ?> Tk
                                                    </span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #666;">No customization options available for this category.</p>
                    <?php endif; ?>
                    
                    <div class="submit-section">
                        <div class="price-breakdown">
                            <span id="basePriceText">Base Price: <?php echo number_format($food_categories[$selected_food]['price'], 2); ?> Tk</span>
                        </div>
                        <div class="price-display">
                            Total: <span id="totalPrice"><?php echo number_format($food_categories[$selected_food]['price'], 2); ?></span> Tk
                        </div>
                        <button type="submit" name="submit_order" class="submit-btn">Add to Cart</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Cart Section -->
    <div class="cart-section">
        <button class="view-cart-btn" onclick="document.getElementById('cart-modal').style.display = 'flex'">
            <span class="cart-icon">🛒</span>
            View Cart
            <span class="cart-counter"><?php echo count($_SESSION['cart']); ?></span>
        </button>
    </div>
    
    <!-- Cart Modal -->
    <div id="cart-modal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title">Your Cart</h2>
                <button class="close-modal" onclick="document.getElementById('cart-modal').style.display = 'none'">&times;</button>
            </div>
            
            <?php if(count($_SESSION['cart']) > 0): ?>
                <div class="cart-items">
                    <?php foreach($_SESSION['cart'] as $cart_id => $item): ?>
                        <div class="cart-item">
                            <div class="cart-item-info">
                                <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <?php if(!empty($item['customizations'])): ?>
                                    <div class="cart-item-customize"><?php echo htmlspecialchars($item['customizations']); ?></div>
                                <?php endif; ?>
                                <div class="cart-item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                            </div>
                            <div class="cart-item-price">
                                <?php echo number_format($item['subtotal'], 2); ?> Tk
                                <a href="customize.php?remove=<?php echo $cart_id; ?>" class="cart-item-remove">✕</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-total">
                    <span>Total:</span>
                    <span><?php echo number_format($cart_total, 2); ?> Tk</span>
                </div>
                
                <div class="cart-buttons">
                    <button class="continue-shopping" onclick="document.getElementById('cart-modal').style.display = 'none'">Continue Shopping</button>
                    <a href="payment.php" class="checkout-btn">Make Payment</a>
                </div>
            <?php else: ?>
                <p style="text-align: center; padding: 20px;">Your cart is empty.</p>
                <div class="cart-buttons">
                    <button class="continue-shopping" onclick="document.getElementById('cart-modal').style.display = 'none'">Continue Shopping</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if(isset($_GET['added'])): ?>
    <div class="notification" id="added-notification">
        Item added to cart!
    </div>
    <?php endif; ?>
    
    <?php if(isset($_GET['removed'])): ?>
    <div class="notification" id="removed-notification" style="background-color: #ff5722;">
        Item removed from cart!
    </div>
    <?php endif; ?>
    
    <script>
        // Store base prices and ingredient prices
        const basePrices = <?php echo json_encode(array_map(function($cat) { return $cat['price']; }, $food_categories)); ?>;
        const ingredientPrices = <?php echo json_encode($ingredient_prices); ?>;
        const currentCategory = "<?php echo $selected_food; ?>";
        
        // Calculate and update total price
        function updatePrice() {
            let basePrice = basePrices[currentCategory];
            let additionalPrice = 0;
            
            // Get all selected options
            const selectedOptions = document.querySelectorAll('.ingredient-option:checked');
            
            selectedOptions.forEach(option => {
                const price = parseFloat(option.dataset.price);
                additionalPrice += price;
            });
            
            const totalPrice = basePrice + additionalPrice;
            
            // Update the display
            document.getElementById('totalPrice').textContent = totalPrice.toFixed(2);
        }
        
        // Add event listeners to all radio buttons
        document.addEventListener('DOMContentLoaded', function() {
            const options = document.querySelectorAll('.ingredient-option');
            options.forEach(option => {
                option.addEventListener('change', updatePrice);
            });
            
            // Initial price calculation
            updatePrice();
        });
        
        // Show notifications
        window.onload = function() {
            <?php if(isset($_GET['added'])): ?>
            document.getElementById('added-notification').style.display = 'block';
            setTimeout(function() {
                document.getElementById('added-notification').style.display = 'none';
            }, 3000);
            <?php endif; ?>
            
            <?php if(isset($_GET['removed'])): ?>
            document.getElementById('removed-notification').style.display = 'block';
            setTimeout(function() {
                document.getElementById('removed-notification').style.display = 'none';
            }, 3000);
            <?php endif; ?>
        };
    </script>
</body>
</html>