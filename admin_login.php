<?php
// Start session
session_start();

// Include database connection
include 'dbconnect.php';

// Initialize variables
$error_message = "";

// Process login request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if logout was requested
    if (isset($_POST['logout'])) {
        // Clear all session variables
        $_SESSION = array();
        
        // Destroy the session
        session_destroy();
        
        // Redirect to login page
        header("Location: admin_login.php");
        exit();
    }
    
    // Process login attempt
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        // Validate input
        if (empty($email) || empty($password)) {
            $error_message = "Email and password are required";
        } else {
            // Prepare SQL statement to check admin credentials
            $stmt = $conn->prepare("
                SELECT a.Admin_ID, a.Roles, u.Email, u.Password 
                FROM admins a 
                JOIN users u ON a.Admin_ID = u.User_ID
                WHERE u.Email = ?
            ");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $admin = $result->fetch_assoc();
                
                // Verify password (assuming passwords are hashed with password_hash)
                if (password_verify($password, $admin['Password']) || $password === $admin['Password']) {
                    // Set session variables
                    $_SESSION['admin_id'] = $admin['Admin_ID'];
                    $_SESSION['is_admin'] = true;
                    
                    // Redirect to admin panel
                    header("Location: admin_panel.php");
                    exit();
                } else {
                    $error_message = "Email or Password is wrong";
                }
            } else {
                $error_message = "Email or Password is wrong";
            }
            $stmt->close();
        }
    }
}

// If already logged in as admin, redirect to admin panel
if (isset($_SESSION['admin_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header("Location: admin_panel.php");
    exit();
}

// Close database connection if opened
if (isset($conn)) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Bites - Admin Login</title>
    <style>
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #1e1e1e;
            color: #e0e0e0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        
        .login-container {
            background-color: #252525;
            border-radius: 5px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            padding: 30px;
            width: 350px;
            text-align: center;
        }
        
        .logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
        }
        
        .admin-badge {
            background-color: #444;
            color: #fff;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #cccccc;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #333;
            border-radius: 3px;
            background-color: #333;
            color: #fff;
            box-sizing: border-box;
        }
        
        .error-message {
            color: #ff6b6b;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .login-btn {
            background-color: #444;
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 3px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .login-btn:hover {
            background-color: #555;
        }
        
        .back-link {
            display: block;
            margin-top: 20px;
            color: #aaa;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link:hover {
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="logo.jpeg" alt="Custom Bites Logo" class="logo" onerror="this.onerror=null; this.src='data:image/svg+xml;charset=utf-8,%3Csvg xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22 width%3D%22120%22 height%3D%22120%22 viewBox%3D%220 0 120 120%22%3E%3Crect width%3D%22120%22 height%3D%22120%22 fill%3D%22%23ff5722%22%2F%3E%3Ctext x%3D%2260%22 y%3D%2260%22 font-family%3D%22Arial%22 font-size%3D%2220%22 fill%3D%22%23fff%22 text-anchor%3D%22middle%22 dominant-baseline%3D%22middle%22%3ECustom%20Bites%3C%2Ftext%3E%3C%2Fsvg%3E'">
        <span class="admin-badge">Administrator</span>
        
        <h1>Admin Login</h1>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="login-btn">Login</button>
        </form>
        
        <a href="index.php" class="back-link">← Back to User Login</a>
    </div>
</body>
</html>