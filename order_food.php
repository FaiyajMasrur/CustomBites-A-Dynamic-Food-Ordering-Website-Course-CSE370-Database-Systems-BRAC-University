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

// Handle Add to Cart
if(isset($_POST['add_to_cart'])) {
    $item_id = $_POST['item_id'];
    $quantity = $_POST['quantity'];
    $customizations = isset($_POST['customizations']) ? $_POST['customizations'] : '';
    
    // Get item details from database
    $stmt = $conn->prepare("SELECT Name, Price FROM menu_items WHERE Item_ID = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();
    
    // Calculate subtotal
    $subtotal = $item['Price'] * $quantity;
    
    // Generate unique cart item ID
    $cart_item_id = $item_id . '_' . time();
    
    // Add to cart session
    $_SESSION['cart'][$cart_item_id] = array(
        'item_id' => $item_id,
        'name' => $item['Name'],
        'price' => $item['Price'],
        'quantity' => $quantity,
        'customizations' => $customizations,
        'subtotal' => $subtotal,
        'is_custom' => false
    );
    
    // Redirect to avoid form resubmission
    header("Location: order_food.php?added=1");
    exit();
}

// Remove from cart
if(isset($_GET['remove']) && isset($_SESSION['cart'][$_GET['remove']])) {
    unset($_SESSION['cart'][$_GET['remove']]);
    header("Location: order_food.php?removed=1");
    exit();
}

// Fetch menu categories
$categories = array("Burger", "Pizza", "Pasta", "Desserts", "Drinks");

// Determine which category to display
$activeCategory = isset($_GET['category']) ? $_GET['category'] : $categories[0];

// Get menu items for the active category only
$stmt = $conn->prepare("SELECT m.Item_ID, m.Name, m.Description, m.Price 
                      FROM menu_items m 
                      JOIN menu_items_category c ON m.Item_ID = c.Item_ID 
                      WHERE c.Category = ?");
$stmt->bind_param("s", $activeCategory);
$stmt->execute();
$result = $stmt->get_result();

$items = array();
while($row = $result->fetch_assoc()) {
    $items[] = $row;
}

$stmt->close();

// Fetch user details
$stmt = $conn->prepare("SELECT First_name FROM users WHERE User_ID = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$first_name = $user['First_name'];
$stmt->close();

// Calculate cart total
$cart_total = 0;
foreach($_SESSION['cart'] as $item) {
    $cart_total += $item['subtotal'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Bites - Order Food</title>
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
        
        /* Menu Section */
        .menu-section {
            padding: 30px 0;
        }
        
        .menu-tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .menu-tab {
            padding: 10px 25px;
            background-color: #f0f0f0;
            border: none;
            margin: 5px;
            cursor: pointer;
            font-size: 16px;
            border-radius: 25px;
            transition: all 0.3s;
            display: inline-block;
            text-decoration: none;
            color: #333;
        }
        
        .menu-tab.active {
            background-color: #ff5722;
            color: #fff;
        }
        
        .menu-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .menu-item {
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .menu-item:hover {
            transform: translateY(-5px);
        }
        
        .menu-item-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .menu-item-img-fallback {
            width: 100%;
            height: 200px;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 16px;
        }
        
        .menu-item-info {
            padding: 20px;
        }
        
        .menu-item-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .menu-item-desc {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .menu-item-price {
            color: #ff5722;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 15px;
        }
        
        .order-controls {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .quantity-input {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .quantity-input label {
            margin-right: 10px;
            font-weight: 500;
        }
        
        .quantity-input input {
            width: 60px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .customize-checkbox {
            margin-bottom: 10px;
        }
        
        .customization-area {
            display: none;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        
        .customization-area textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            min-height: 80px;
        }
        
        .add-to-cart-btn {
            background-color: #ff5722;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-align: center;
            font-weight: bold;
            width: 100%;
        }
        
        .add-to-cart-btn:hover {
            background-color: #e64a19;
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
        
        /* Empty state */
        .no-items {
            grid-column: 1 / -1;
            text-align: center;
            padding: 30px;
            font-size: 18px;
            color: #666;
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
                    <li><a href="order_food.php" class="active">Order Food</a></li>
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
            <h1 class="page-title">Order Food</h1>
            
            <div class="menu-section">
                <div class="menu-tabs">
                    <?php foreach($categories as $category): ?>
                        <a href="?category=<?php echo urlencode($category); ?>" class="menu-tab <?php echo ($activeCategory == $category) ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($category); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="menu-items">
                    <?php if(count($items) > 0): ?>
                        <?php foreach($items as $item): ?>
                            <div class="menu-item">
                                <?php 
                                $imagePath = "menu/" . strtolower($activeCategory) . "/" . $item['Item_ID'] . ".jpg";
                                ?>
                                <div class="menu-item-img-container">
                                    <img 
                                        src="<?php echo htmlspecialchars($imagePath); ?>" 
                                        alt="<?php echo htmlspecialchars($item['Name']); ?>" 
                                        class="menu-item-img"
                                        onerror="this.onerror=null; this.parentNode.innerHTML='<div class=\'menu-item-img-fallback\'><?php echo htmlspecialchars($item['Name']); ?></div>';"
                                    >
                                </div>
                                <div class="menu-item-info">
                                    <h3 class="menu-item-title"><?php echo htmlspecialchars($item['Name']); ?></h3>
                                    <p class="menu-item-desc"><?php echo htmlspecialchars($item['Description']); ?></p>
                                    <div class="menu-item-price"><?php echo number_format($item['Price'], 2); ?> Tk</div>
                                    
                                    <div class="order-controls">
                                        <form method="post" action="order_food.php">
                                            <input type="hidden" name="item_id" value="<?php echo $item['Item_ID']; ?>">
                                            <div class="quantity-input">
                                                <label for="quantity-<?php echo $item['Item_ID']; ?>">Quantity:</label>
                                                <input type="number" min="1" value="1" name="quantity" id="quantity-<?php echo $item['Item_ID']; ?>">
                                            </div>
                                            
                                            <div class="customize-checkbox">
                                                <label>
                                                    <input type="checkbox" onchange="toggleCustomizationField(this, '<?php echo $item['Item_ID']; ?>')"> Add customization
                                                </label>
                                            </div>
                                            
                                            <div id="customization-<?php echo $item['Item_ID']; ?>" class="customization-area">
                                                <textarea name="customizations" placeholder="Enter your customization requests..."></textarea>
                                            </div>
                                            
                                            <button type="submit" name="add_to_cart" class="add-to-cart-btn">Add to Cart</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-items">No items available in this category.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
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
                                <a href="order_food.php?remove=<?php echo $cart_id; ?>" class="cart-item-remove">✕</a>
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
        
        // Function to toggle customization field
        function toggleCustomizationField(checkbox, itemId) {
            var customizationField = document.getElementById('customization-' + itemId);
            if (checkbox.checked) {
                customizationField.style.display = 'block';
            } else {
                customizationField.style.display = 'none';
            }
        }
    </script>
</body>
</html>