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

// Fetch all events
$stmt = $conn->prepare("SELECT Event_ID, Name, Description, Status FROM events ORDER BY Event_ID");
$stmt->execute();
$result = $stmt->get_result();

$events = array();
while($row = $result->fetch_assoc()) {
    $events[] = $row;
}

$stmt->close();

// Auto-rotate events based on 7-day cycles
if(count($events) > 0) {
    // Define a fixed start date for the cycle (you can change this to any date you want)
    $cycleStartDate = strtotime('2025-01-01'); // Starting point for the cycle
    $currentDate = time();
    
    // Calculate how many days have passed since cycle start
    $daysPassed = floor(($currentDate - $cycleStartDate) / (60 * 60 * 24));
    
    // Calculate which week we're in (0-based)
    $weekNumber = floor($daysPassed / 7);
    
    // Calculate which event should be ongoing (rotate through all events)
    $totalEvents = count($events);
    $ongoingIndex = $weekNumber % $totalEvents;
    $scheduledIndex = ($ongoingIndex + 1) % $totalEvents;
    
    // Update event statuses based on rotation
    for($i = 0; $i < count($events); $i++) {
        if($i == $ongoingIndex) {
            $events[$i]['Status'] = 'Ongoing';
        } elseif($i == $scheduledIndex) {
            $events[$i]['Status'] = 'Scheduled';
        } else {
            $events[$i]['Status'] = 'Coming Soon';
        }
    }
}

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
    <title>Custom Bites - Events</title>
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
        
        /* Page Title Section */
        .page-title {
            margin-top: 100px;
            text-align: center;
            padding: 20px 0;
        }
        
        .page-title h2 {
            font-size: 36px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .page-title p {
            color: #666;
            font-size: 18px;
            max-width: 700px;
            margin: 0 auto;
        }
        
        /* Events Section */
        .events-section {
            padding: 40px 0 80px;
        }
        
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .event-card {
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .event-card:hover {
            transform: translateY(-5px);
        }
        
        .event-card-content {
            padding: 25px;
        }
        
        .event-title {
            font-size: 22px;
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
        }
        
        .event-description {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .event-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .status-ongoing {
            background-color: #e6f7e6;
            color: #28a745;
        }
        
        .status-scheduled {
            background-color: #fff3cd;
            color: #ffc107;
        }
        
        .status-coming-soon {
            background-color: #f0f0f0;
            color: #6c757d;
        }
        
        /* Notice Section */
        .notice-section {
            background-color: #f5f5f5;
            padding: 30px 0;
            margin-top: 40px;
            border-radius: 10px;
        }
        
        .notice-content {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        
        .notice-content h3 {
            color: #ff5722;
            margin-bottom: 15px;
        }
        
        .notice-content p {
            color: #666;
            line-height: 1.6;
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
                    <li><a href="events.php" class="active">Events</a></li>
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
    
    <section class="page-title">
        <div class="container">
            <h2>Food Events</h2>
            <p>Join our exciting culinary events and unleash your creativity with food!</p>
        </div>
    </section>
    
    <section class="events-section">
        <div class="container">
            <div class="events-grid">
                <?php if(count($events) > 0): ?>
                    <?php foreach($events as $event): ?>
                        <div class="event-card">
                            <div class="event-card-content">
                                <h3 class="event-title"><?php echo htmlspecialchars($event['Name']); ?></h3>
                                <p class="event-description"><?php echo htmlspecialchars($event['Description']); ?></p>
                                <?php 
                                $statusClass = '';
                                switch($event['Status']) {
                                    case 'Ongoing':
                                        $statusClass = 'status-ongoing';
                                        break;
                                    case 'Scheduled':
                                        $statusClass = 'status-scheduled';
                                        break;
                                    case 'Coming Soon':
                                        $statusClass = 'status-coming-soon';
                                        break;
                                }
                                ?>
                                <span class="event-status <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($event['Status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-events">
                        <p>No events are currently available. Check back soon!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="notice-section">
                <div class="notice-content">
                    <h3>Event Participation Information</h3>
                    <p>No registration needed. These are open events - anyone can join! Please note that we have limited participation available, so people who arrive earlier will be prioritized. Come and enjoy the culinary experience with us!</p>
                    <p style="margin-top: 15px; font-style: italic; color: #999;">Events rotate weekly - check back each week for new ongoing events!</p>
                </div>
            </div>
        </div>
    </section>
</body>
</html>