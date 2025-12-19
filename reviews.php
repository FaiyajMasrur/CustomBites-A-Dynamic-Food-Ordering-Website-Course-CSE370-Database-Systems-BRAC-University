<?php
// Start session
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
include 'dbconnect.php';

// Get user's information
$stmt = $conn->prepare("SELECT First_name FROM users WHERE User_ID = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$first_name = $user['First_name'];
$stmt->close();

// Check if user is also a customer
$stmt = $conn->prepare("SELECT Customer_ID FROM customers WHERE Customer_ID = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$isCustomer = $result->num_rows > 0;
$stmt->close();

// Get menu items
$stmt = $conn->prepare("SELECT Item_ID, Name FROM menu_items ORDER BY Name");
$stmt->execute();
$menuItems = $stmt->get_result();
$stmt->close();

// Get events
$stmt = $conn->prepare("SELECT Event_ID, Name FROM events ORDER BY Name");
$stmt->execute();
$events = $stmt->get_result();
$stmt->close();

// Process form submission
$message = '';
$messageType = '';

// Set default review type (for displaying appropriate fields after form submission)
$reviewType = isset($_POST['review_type']) ? $_POST['review_type'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && $isCustomer) {
    // Validate inputs
    $comment = trim($_POST['comment']);
    $reviewType = $_POST['review_type'];
    
    // Check if comment is empty
    if (empty($comment)) {
        $message = "Please enter a comment for your review.";
        $messageType = "error";
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Insert into reviews table
            $today = date("Y-m-d");
            $stmt = $conn->prepare("INSERT INTO reviews (Date, Comment) VALUES (?, ?)");
            $stmt->bind_param("ss", $today, $comment);
            $stmt->execute();
            
            // Get the last inserted review ID
            $reviewId = $conn->insert_id;
            $stmt->close();
            
            // Insert into based_on table
            $eventId = null;
            $orderId = null;
            
            if ($reviewType == 'event' && isset($_POST['event_id'])) {
                $eventId = $_POST['event_id'];
                // Insert into based_on with event_id
                $stmt = $conn->prepare("INSERT INTO based_on (Review_ID, Event_ID, Order_ID) VALUES (?, ?, NULL)");
                $stmt->bind_param("ii", $reviewId, $eventId);
                $stmt->execute();
                $stmt->close();
                
                // Insert into participates ONLY when reviewing an event
                $customerId = $_SESSION['user_id'];
                $stmt = $conn->prepare("INSERT INTO participates (Customer_ID, Review_ID, Event_ID) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $customerId, $reviewId, $eventId);
                $stmt->execute();
                $stmt->close();
            } elseif ($reviewType == 'menu' && isset($_POST['item_id'])) {
                // For menu items, we're storing in review_items table
                $itemId = $_POST['item_id'];
                
                // Get the item name
                $stmt = $conn->prepare("SELECT Name FROM menu_items WHERE Item_ID = ?");
                $stmt->bind_param("i", $itemId);
                $stmt->execute();
                $itemResult = $stmt->get_result();
                $itemData = $itemResult->fetch_assoc();
                $itemName = $itemData['Name'];
                $stmt->close();
                
                // Insert into review_items
                $stmt = $conn->prepare("INSERT INTO review_items (Items, Review_ID) VALUES (?, ?)");
                $stmt->bind_param("si", $itemName, $reviewId);
                $stmt->execute();
                $stmt->close();
                
                // Insert into based_on with both NULL
                $stmt = $conn->prepare("INSERT INTO based_on (Review_ID, Event_ID, Order_ID) VALUES (?, NULL, NULL)");
                $stmt->bind_param("i", $reviewId);
                $stmt->execute();
                $stmt->close();
                
                // Do NOT insert into participates for menu-only reviews
            } elseif ($reviewType == 'both' && isset($_POST['item_id']) && isset($_POST['event_id'])) {
                $itemId = $_POST['item_id'];
                $eventId = $_POST['event_id'];
                
                // Get the item name
                $stmt = $conn->prepare("SELECT Name FROM menu_items WHERE Item_ID = ?");
                $stmt->bind_param("i", $itemId);
                $stmt->execute();
                $itemResult = $stmt->get_result();
                $itemData = $itemResult->fetch_assoc();
                $itemName = $itemData['Name'];
                $stmt->close();
                
                // Insert into review_items
                $stmt = $conn->prepare("INSERT INTO review_items (Items, Review_ID) VALUES (?, ?)");
                $stmt->bind_param("si", $itemName, $reviewId);
                $stmt->execute();
                $stmt->close();
                
                // Insert into based_on with event_id
                $stmt = $conn->prepare("INSERT INTO based_on (Review_ID, Event_ID, Order_ID) VALUES (?, ?, NULL)");
                $stmt->bind_param("ii", $reviewId, $eventId);
                $stmt->execute();
                $stmt->close();
                
                // Insert into participates ONLY when reviewing an event
                $customerId = $_SESSION['user_id'];
                $stmt = $conn->prepare("INSERT INTO participates (Customer_ID, Review_ID, Event_ID) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $customerId, $reviewId, $eventId);
                $stmt->execute();
                $stmt->close();
            }
            
            // Commit the transaction
            $conn->commit();
            
            $message = "Your review has been submitted successfully!";
            $messageType = "success";
            
            // Reset the review type after successful submission
            $reviewType = '';
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $message = "Error submitting your review. Please try again. " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// Get recent reviews
$recentReviews = [];
$stmt = $conn->prepare("
    SELECT r.Review_ID, r.Date, r.Comment, 
           GROUP_CONCAT(DISTINCT ri.Items) as Items, 
           e.Name as EventName,
           u.First_name, u.Last_name
    FROM reviews r
    LEFT JOIN review_items ri ON r.Review_ID = ri.Review_ID
    LEFT JOIN based_on b ON r.Review_ID = b.Review_ID
    LEFT JOIN events e ON b.Event_ID = e.Event_ID
    LEFT JOIN participates p ON r.Review_ID = p.Review_ID
    LEFT JOIN users u ON p.Customer_ID = u.User_ID
    GROUP BY r.Review_ID
    ORDER BY r.Date DESC
    LIMIT 10
");
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()) {
    $recentReviews[] = $row;
}
$stmt->close();

$conn->close();

// Helper function to determine if a field should be displayed
function shouldDisplayField($fieldType, $currentReviewType) {
    if (empty($currentReviewType)) {
        return false;
    }
    
    if ($fieldType === 'menu' && ($currentReviewType === 'menu' || $currentReviewType === 'both')) {
        return true;
    }
    
    if ($fieldType === 'event' && ($currentReviewType === 'event' || $currentReviewType === 'both')) {
        return true;
    }
    
    return false;
}

// Helper function to determine if a field is required
function isFieldRequired($fieldType, $currentReviewType) {
    return shouldDisplayField($fieldType, $currentReviewType);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Bites - Reviews</title>
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
            margin-top: 90px;
            padding: 30px 0;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 40px;
            font-size: 32px;
            color: #333;
        }
        
        .reviews-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        
        .write-review {
            flex: 1;
            min-width: 300px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }
        
        .recent-reviews {
            flex: 1;
            min-width: 300px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }
        
        .review-form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            font-family: inherit;
        }
        
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .btn {
            display: inline-block;
            background-color: #ff5722;
            color: #fff;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background-color: #e64a19;
        }
        
        .btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Review Cards */
        .review-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #ff5722;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .review-author {
            font-weight: bold;
        }
        
        .review-date {
            color: #777;
            font-size: 14px;
        }
        
        .review-subject {
            margin-bottom: 10px;
            font-weight: 500;
            color: #444;
        }
        
        .review-comment {
            color: #555;
            line-height: 1.5;
        }
        
        .no-reviews {
            text-align: center;
            color: #777;
            padding: 20px 0;
        }
        
        /* Conditional Form Fields */
        .conditional-field {
            display: none;
        }
        
        /* Required Field Indicator */
        .required:after {
            content: "*";
            color: #ff5722;
            margin-left: 5px;
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
                    <li><a href="reviews.php" class="active">Reviews</a></li>
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
            <h2 class="section-title">Reviews</h2>
            
            <div class="reviews-container">
                <div class="write-review">
                    <h3>Write a Review</h3>
                    
                    <?php if(!$isCustomer): ?>
                        <p>You must be registered as a customer to leave a review.</p>
                    <?php else: ?>
                        <?php if(!empty($message)): ?>
                            <div class="message <?php echo $messageType; ?>">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form class="review-form" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="form-group">
                                <label for="review_type" class="required">What are you reviewing?</label>
                                <select id="review_type" name="review_type" required onchange="this.form.submit()">
                                    <option value="" <?php echo empty($reviewType) ? 'selected' : ''; ?>>Select Review Type</option>
                                    <option value="menu" <?php echo $reviewType === 'menu' ? 'selected' : ''; ?>>Menu Item</option>
                                    <option value="event" <?php echo $reviewType === 'event' ? 'selected' : ''; ?>>Event</option>
                                    <option value="both" <?php echo $reviewType === 'both' ? 'selected' : ''; ?>>Both Menu Item and Event</option>
                                </select>
                            </div>
                            
                            <div id="menu_field" class="form-group" style="<?php echo shouldDisplayField('menu', $reviewType) ? 'display:block;' : 'display:none;'; ?>">
                                <label for="item_id" class="required">Menu Item</label>
                                <select id="item_id" name="item_id" <?php echo isFieldRequired('menu', $reviewType) ? 'required' : ''; ?>>
                                    <option value="">Select Menu Item</option>
                                    <?php 
                                    // Reset the pointer to the beginning
                                    if ($menuItems) {
                                        $menuItems->data_seek(0);
                                        while($item = $menuItems->fetch_assoc()): 
                                            $selected = isset($_POST['item_id']) && $_POST['item_id'] == $item['Item_ID'] ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $item['Item_ID']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($item['Name']); ?></option>
                                    <?php 
                                        endwhile;
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div id="event_field" class="form-group" style="<?php echo shouldDisplayField('event', $reviewType) ? 'display:block;' : 'display:none;'; ?>">
                                <label for="event_id" class="required">Event</label>
                                <select id="event_id" name="event_id" <?php echo isFieldRequired('event', $reviewType) ? 'required' : ''; ?>>
                                    <option value="">Select Event</option>
                                    <?php 
                                    // Reset the pointer to the beginning
                                    if ($events) {
                                        $events->data_seek(0);
                                        while($event = $events->fetch_assoc()): 
                                            $selected = isset($_POST['event_id']) && $_POST['event_id'] == $event['Event_ID'] ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $event['Event_ID']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($event['Name']); ?></option>
                                    <?php 
                                        endwhile;
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="comment" class="required">Your Review</label>
                                <textarea id="comment" name="comment" required placeholder="Share your thoughts about our food, service, or events..."><?php echo isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : ''; ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn">Submit Review</button>
                            
                            <?php if(!empty($reviewType)): ?>
                                <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn" style="background-color: #999; margin-left: 10px;">Reset</a>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </div>
                
                <div class="recent-reviews">
                    <h3>Recent Reviews</h3>
                    
                    <?php if(empty($recentReviews)): ?>
                        <div class="no-reviews">
                            <p>No reviews yet. Be the first to leave a review!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($recentReviews as $review): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <span class="review-author"><?php echo htmlspecialchars($review['First_name'].' '.$review['Last_name']); ?></span>
                                    <span class="review-date"><?php echo date('F j, Y', strtotime($review['Date'])); ?></span>
                                </div>
                                
                                <div class="review-subject">
                                    <?php
                                    $subjectParts = [];
                                    if(!empty($review['Items'])) {
                                        $subjectParts[] = "Menu Item: " . htmlspecialchars($review['Items']);
                                    }
                                    if(!empty($review['EventName'])) {
                                        $subjectParts[] = "Event: " . htmlspecialchars($review['EventName']);
                                    }
                                    echo !empty($subjectParts) ? implode(' | ', $subjectParts) : "General Review";
                                    ?>
                                </div>
                                
                                <div class="review-comment">
                                    <?php echo nl2br(htmlspecialchars($review['Comment'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>