<?php
// Start session
session_start();

// Check if user is logged in and is an admin
if(!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Include database connection
include 'dbconnect.php';

// Handle order status updates
if(isset($_POST['update_order_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE order_details SET Status = ? WHERE Order_ID = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    
    if($stmt->execute()) {
        if($stmt->affected_rows > 0) {
            $status_message = "Order #" . $order_id . " status updated to " . $new_status;
        } else {
            $error_message = "No changes made to Order #" . $order_id;
        }
    } else {
        $error_message = "Failed to update order status: " . $conn->error;
    }
    $stmt->close();
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=orders&updated=1");
    exit();
}

// Handle inventory updates
if(isset($_POST['update_inventory'])) {
    $ingredient_id = intval($_POST['ingredient_id']);
    $new_quantity = intval($_POST['new_quantity']);
    
    $stmt = $conn->prepare("UPDATE inventory SET Quantity = ? WHERE Ingredient_ID = ?");
    $stmt->bind_param("ii", $new_quantity, $ingredient_id);
    
    if($stmt->execute()) {
        if($stmt->affected_rows > 0) {
            $inventory_message = "Inventory item #" . $ingredient_id . " quantity updated to " . $new_quantity;
        } else {
            $inventory_error = "No changes made to inventory item #" . $ingredient_id;
        }
    } else {
        $inventory_error = "Failed to update inventory: " . $conn->error;
    }
    $stmt->close();
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=inventory&updated=1");
    exit();
}

// Handle adding new inventory item
if(isset($_POST['add_ingredient'])) {
    $ingredient_name = trim($_POST['ingredient_name']);
    $quantity = intval($_POST['quantity']);
    
    $stmt = $conn->prepare("INSERT INTO inventory (Ingredient_Name, Quantity) VALUES (?, ?)");
    $stmt->bind_param("si", $ingredient_name, $quantity);
    
    if($stmt->execute()) {
        $inventory_message = "New inventory item '" . $ingredient_name . "' added successfully";
    } else {
        $inventory_error = "Failed to add inventory item: " . $conn->error;
    }
    $stmt->close();
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=inventory&added=1");
    exit();
}

// Handle inventory item deletion
if(isset($_GET['delete_ingredient']) && isset($_GET['id'])) {
    $ingredient_id = intval($_GET['id']);
    
    // First update any admins that reference this ingredient
    $stmt = $conn->prepare("UPDATE admins SET Ingredient_ID = NULL WHERE Ingredient_ID = ?");
    $stmt->bind_param("i", $ingredient_id);
    $stmt->execute();
    $stmt->close();
    
    // Then delete the ingredient
    $stmt = $conn->prepare("DELETE FROM inventory WHERE Ingredient_ID = ?");
    $stmt->bind_param("i", $ingredient_id);
    
    if($stmt->execute()) {
        if($stmt->affected_rows > 0) {
            $inventory_message = "Inventory item #" . $ingredient_id . " deleted successfully";
        } else {
            $inventory_error = "Inventory item #" . $ingredient_id . " not found";
        }
    } else {
        $inventory_error = "Failed to delete inventory item: " . $conn->error;
    }
    $stmt->close();
    
    // Redirect to prevent multiple deletions
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=inventory&deleted=1");
    exit();
}

// Get admin info
$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT a.Roles, u.First_name, u.Last_name FROM admins a JOIN users u ON a.Admin_ID = u.User_ID WHERE a.Admin_ID = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$admin_name = $admin['First_name'] . " " . $admin['Last_name'];
$admin_role = $admin['Roles'];
$stmt->close();

// Determine which tab to display
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'orders';

// Show success messages after redirect
if($active_tab == 'orders' && isset($_GET['updated'])) {
    $status_message = "Order status updated successfully";
}
if($active_tab == 'inventory' && isset($_GET['updated'])) {
    $inventory_message = "Inventory updated successfully";
}
if($active_tab == 'inventory' && isset($_GET['added'])) {
    $inventory_message = "New ingredient added successfully";
}
if($active_tab == 'inventory' && isset($_GET['deleted'])) {
    $inventory_message = "Inventory item deleted successfully";
}

// Fetch recent orders for Orders tab
$orders = array();
if($active_tab == 'orders') {
    // Fetch all orders from order_details
    $stmt = $conn->prepare("
        SELECT od.Order_ID, od.Subtotal, od.Status
        FROM order_details od
        ORDER BY od.Order_ID DESC
        LIMIT 50
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while($row = $result->fetch_assoc()) {
        $order_id = $row['Order_ID'];
        
        // Get order items for each order
        $items_stmt = $conn->prepare("
            SELECT odi.Item_Name, odi.Quantity, odi.Price
            FROM order_details_items odi
            WHERE odi.Order_ID = ?
        ");
        $items_stmt->bind_param("i", $order_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        
        $items = array();
        while($item = $items_result->fetch_assoc()) {
            $items[] = $item;
        }
        $items_stmt->close();
        
        // Get customizations for each order
        $custom_stmt = $conn->prepare("
            SELECT odc.Item_Name, odc.Customizations
            FROM order_details_customization odc
            WHERE odc.Order_ID = ?
        ");
        $custom_stmt->bind_param("i", $order_id);
        $custom_stmt->execute();
        $custom_result = $custom_stmt->get_result();
        
        $customizations = array();
        while($custom = $custom_result->fetch_assoc()) {
            $customizations[] = $custom;
        }
        $custom_stmt->close();
        
        // Add items and customizations to the order
        $row['items'] = $items;
        $row['customizations'] = $customizations;
        $orders[] = $row;
    }
    $stmt->close();
}

// Fetch inventory for Inventory tab
$inventory = array();
if($active_tab == 'inventory') {
    $stmt = $conn->prepare("SELECT * FROM inventory ORDER BY Ingredient_Name");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while($row = $result->fetch_assoc()) {
        $inventory[] = $row;
    }
    $stmt->close();
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Bites - Admin Panel</title>
    <style>
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #1e1e1e;
            color: #e0e0e0;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header Styles */
        header {
            background-color: #121212;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
            padding: 15px 20px;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 35px;
            margin-right: 10px;
        }
        
        .logo h1 {
            font-size: 20px;
            margin: 0;
            color: #cccccc;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
        }
        
        .admin-badge {
            background-color: #444;
            color: #fff;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            margin-right: 15px;
        }
        
        .admin-name {
            margin-right: 15px;
            font-weight: 500;
        }
        
        .logout-btn {
            background-color: #333;
            border: 1px solid #444;
            color: #ccc;
            padding: 5px 12px;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            background-color: #444;
            color: #fff;
        }
        
        /* Main Content Styles */
        main {
            margin-top: 70px;
            padding: 20px 0;
            min-height: calc(100vh - 70px);
        }
        
        .dashboard-header {
            margin-bottom: 30px;
        }
        
        .dashboard-header h2 {
            font-size: 26px;
            margin-bottom: 10px;
            color: #cccccc;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #333;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            margin-right: 5px;
            background-color: #252525;
            border: 1px solid #333;
            border-bottom: none;
            border-radius: 3px 3px 0 0;
            cursor: pointer;
            color: #aaa;
            text-decoration: none;
        }
        
        .tab:hover {
            background-color: #2a2a2a;
            color: #ddd;
        }
        
        .tab.active {
            background-color: #333;
            color: #fff;
        }
        
        /* Message Styles */
        .message {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 3px;
        }
        
        .success {
            background-color: #1e3a1e;
            color: #7bff7b;
            border: 1px solid #2e5a2e;
        }
        
        .error {
            background-color: #3a1e1e;
            color: #ff7b7b;
            border: 1px solid #5a2e2e;
        }
        
        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
            margin-bottom: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #252525;
            margin-bottom: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        
        th {
            background-color: #333;
            color: #fff;
            font-weight: 500;
        }
        
        tr:hover {
            background-color: #2a2a2a;
        }
        
        .order-details {
            font-size: 14px;
            color: #999;
        }
        
        .status-pending {
            color: #ffd866;
        }
        
        .status-processing {
            color: #78dce8;
        }
        
        .status-completed {
            color: #a9dc76;
        }
        
        .status-cancelled {
            color: #ff6188;
        }
        
        .status-paid {
            color: #a9dc76;
        }
        
        .status-unpaid {
            color: #ffd866;
        }
        
        /* Form Styles */
        .form-row {
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #ccc;
        }
        
        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #444;
            border-radius: 3px;
            background-color: #333;
            color: #eee;
        }
        
        button, .btn {
            background-color: #444;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 3px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        button:hover, .btn:hover {
            background-color: #555;
        }
        
        .btn-danger {
            background-color: #5a2e2e;
        }
        
        .btn-danger:hover {
            background-color: #6a3e3e;
        }
        
        /* Cards for Order Items and Inventory */
        .card {
            background-color: #252525;
            border-radius: 3px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #333;
            padding-bottom: 10px;
        }
        
        .card-header h3 {
            margin: 0;
            font-size: 18px;
            color: #ddd;
        }
        
        .card-body {
            font-size: 14px;
        }
        
        .card-body p {
            margin: 5px 0;
        }
        
        /* Orders Section Specific */
        .order-card {
            margin-bottom: 20px;
        }
        
        .order-summary {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        .order-summary div {
            margin-right: 20px;
            margin-bottom: 10px;
        }
        
        .order-items {
            margin-top: 15px;
        }
        
        .order-item {
            padding: 10px;
            border-bottom: 1px solid #333;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-actions {
            margin-top: 15px;
            display: flex;
            align-items: center;
        }
        
        .order-actions select {
            width: auto;
            margin-right: 10px;
        }
        
        /* Inventory Section Specific */
        .inventory-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .inventory-form {
            display: flex;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        
        .inventory-form .form-group {
            margin-right: 15px;
            margin-bottom: 0;
        }
        
        .inventory-form button {
            height: 38px;
        }
        
        /* Low Inventory Warning */
        .low-inventory {
            background-color: #5a2e2e;
        }
        
        /* Customizations List */
        .customization-list {
            list-style-type: none;
            padding-left: 0;
            margin: 0;
        }
        
        .customization-item {
            padding: 5px 0;
            border-bottom: 1px dashed #333;
            font-size: 13px;
        }
        
        .customization-item:last-child {
            border-bottom: none;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .tabs {
                flex-wrap: wrap;
            }
            
            .tab {
                margin-bottom: 5px;
                width: calc(50% - 10px);
                text-align: center;
            }
            
            .order-actions {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-actions select {
                width: 100%;
                margin-right: 0;
                margin-bottom: 10px;
            }
            
            .inventory-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .inventory-form .form-group {
                margin-right: 0;
                margin-bottom: 10px;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <img src="logo.jpeg" alt="Custom Bites Logo" onerror="this.onerror=null; this.src='data:image/svg+xml;charset=utf-8,%3Csvg xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22 width%3D%2240%22 height%3D%2240%22 viewBox%3D%220 0 40 40%22%3E%3Crect width%3D%2240%22 height%3D%2240%22 fill%3D%22%23333%22%2F%3E%3Ctext x%3D%2220%22 y%3D%2220%22 font-family%3D%22Arial%22 font-size%3D%2210%22 fill%3D%22%23999%22 text-anchor%3D%22middle%22 dominant-baseline%3D%22middle%22%3ELogo%3C%2Ftext%3E%3C%2Fsvg%3E'">
                <h1>Custom Bites Admin</h1>
            </div>
            
            <div class="admin-info">
				<span class="admin-badge"><?php echo htmlspecialchars($admin_role); ?></span>
				<span class="admin-name"><?php echo htmlspecialchars($admin_name); ?></span>
				<form action="logout.php" method="post">
					<input type="hidden" name="admin_logout" value="1">
					<button type="submit" class="logout-btn">Logout</button>
				</form>
			</div>
        </div>
    </header>
    
    <main>
        <div class="container">
            <div class="dashboard-header">
                <h2>Admin Dashboard</h2>
                
                <div class="tabs">
                    <a href="?tab=orders" class="tab <?php echo ($active_tab == 'orders') ? 'active' : ''; ?>">Orders</a>
                    <a href="?tab=inventory" class="tab <?php echo ($active_tab == 'inventory') ? 'active' : ''; ?>">Inventory Management</a>
                </div>
            </div>
            
            <?php if(isset($status_message)): ?>
                <div class="message success">
                    <?php echo htmlspecialchars($status_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
                <div class="message error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($inventory_message)): ?>
                <div class="message success">
                    <?php echo htmlspecialchars($inventory_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($inventory_error)): ?>
                <div class="message error">
                    <?php echo htmlspecialchars($inventory_error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Orders Tab Content -->
            <?php if($active_tab == 'orders'): ?>
                <div id="orders-tab">
                    <h3>Recent Orders</h3>
                    
                    <?php if(count($orders) > 0): ?>
                        <?php foreach($orders as $order): ?>
                            <div class="card order-card">
                                <div class="card-header">
                                    <h3>Order #<?php echo $order['Order_ID']; ?></h3>
                                    <span class="status-<?php echo strtolower($order['Status']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($order['Status'])); ?>
                                    </span>
                                </div>
                                
                                <div class="card-body">
                                    <div class="order-summary">
                                        <div>
                                            <strong>Subtotal:</strong> $<?php echo number_format($order['Subtotal'], 2); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="order-items">
                                        <h4>Items</h4>
                                        <?php if(count($order['items']) > 0): ?>
                                            <?php foreach($order['items'] as $item): ?>
                                                <div class="order-item">
                                                    <p>
                                                        <strong><?php echo htmlspecialchars($item['Item_Name']); ?></strong> x 
                                                        <?php echo $item['Quantity']; ?> - 
                                                        $<?php echo number_format($item['Price'], 2); ?>
                                                    </p>
                                                    
                                                    <?php 
                                                    // Find customizations for this item
                                                    $item_customizations = array_filter($order['customizations'], function($c) use ($item) {
                                                        return $c['Item_Name'] == $item['Item_Name'];
                                                    });
                                                    
                                                    if(count($item_customizations) > 0): 
                                                    ?>
                                                        <div class="item-customizations">
                                                            <p><em>Customizations:</em></p>
                                                            <ul class="customization-list">
                                                                <?php foreach($item_customizations as $custom): ?>
                                                                    <li class="customization-item">
                                                                        <?php echo htmlspecialchars($custom['Customizations']); ?>
                                                                    </li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p>No items found for this order.</p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="order-actions">
                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                            <input type="hidden" name="order_id" value="<?php echo $order['Order_ID']; ?>">
                                            <select name="new_status">
                                                <option value="paid" <?php echo ($order['Status'] == 'paid') ? 'selected' : ''; ?>>Paid</option>
                                                <option value="unpaid" <?php echo ($order['Status'] == 'unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                                            </select>
                                            <button type="submit" name="update_order_status">Update Status</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-body">
                                <p>No orders found.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Inventory Tab Content -->
            <?php if($active_tab == 'inventory'): ?>
                <div id="inventory-tab">
                    <div class="inventory-header">
                        <h3>Inventory Management</h3>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3>Add New Ingredient</h3>
                        </div>
                        <div class="card-body">
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="inventory-form">
                                <div class="form-group">
                                    <label for="ingredient_name">Ingredient Name</label>
                                    <input type="text" id="ingredient_name" name="ingredient_name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="quantity">Quantity</label>
                                    <input type="number" id="quantity" name="quantity" min="0" required>
                                </div>
                                
                                <button type="submit" name="add_ingredient">Add Ingredient</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Ingredient Name</th>
                                    <th>Quantity</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($inventory) > 0): ?>
                                    <?php foreach($inventory as $item): ?>
                                        <tr class="<?php echo ($item['Quantity'] < 10) ? 'low-inventory' : ''; ?>">
                                            <td><?php echo $item['Ingredient_ID']; ?></td>
                                            <td><?php echo htmlspecialchars($item['Ingredient_Name']); ?></td>
                                            <td>
                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display: flex; align-items: center;">
                                                    <input type="hidden" name="ingredient_id" value="<?php echo $item['Ingredient_ID']; ?>">
                                                    <input type="number" name="new_quantity" value="<?php echo $item['Quantity']; ?>" min="0" style="width: 80px; margin-right: 10px;">
                                                    <button type="submit" name="update_inventory">Update</button>
                                                </form>
                                            </td>
                                            <td>
                                                <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?tab=inventory&delete_ingredient=1&id=' . $item['Ingredient_ID']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this ingredient?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4">No inventory items found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>