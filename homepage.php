<?php
// Start session
session_start();

// Check if user is logged in - using consistent lowercase naming
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
include 'dbconnect.php';

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

// Get user's first name
$stmt = $conn->prepare("SELECT First_name FROM users WHERE User_ID = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$first_name = $user['First_name'];
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Bites - Homepage</title>
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
        
        /* Hero Section */
        .hero {
            background-image: url('hero-background.jpg');
            background-size: cover;
            background-position: center;
            height: 500px;
            display: flex;
            align-items: center;
            margin-top: 70px;
            position: relative;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .hero-content {
            position: relative;
            z-index: 10;
            color: #fff;
            max-width: 600px;
            padding: 0 20px;
        }
        
        .hero-content h2 {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .hero-content p {
            font-size: 18px;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-block;
            background-color: #ff5722;
            color: #fff;
            padding: 12px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background-color: #e64a19;
            transform: translateY(-2px);
        }
        
        /* Menu Section */
        .menu-section {
            padding: 60px 0;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 40px;
            font-size: 32px;
            color: #333;
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
            margin-top: 30px;
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
        
        .view-details {
            background-color: #ff5722;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        
        .view-details:hover {
            background-color: #e64a19;
        }
        
        .hidden {
            display: none;
        }
        
        /* No items message */
        .no-items {
            grid-column: 1 / -1;
            text-align: center;
            padding: 30px;
            font-size: 18px;
            color: #666;
        }
        
        /* Fallback for missing images */
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
                    <li><a href="#" class="active">Home</a></li>
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
    
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h2>Welcome to Custom Bites</h2>
                <p>Create your perfect meal with our customizable menu options. Fresh ingredients, amazing taste, just the way you like it!</p>
                <a href="#menu" class="btn">View Menu</a>
            </div>
        </div>
    </section>
    
    <section id="menu" class="menu-section">
        <div class="container">
            <h2 class="section-title">Our Menu</h2>
            
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
                                <div class="menu-item-price">$<?php echo number_format($item['Price'], 2); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-items">No items available in this category.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</body>
</html>