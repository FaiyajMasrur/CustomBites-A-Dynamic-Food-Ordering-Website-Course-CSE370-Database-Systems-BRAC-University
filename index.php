<?php
// Start session
session_start();

// Include database connection
include 'dbconnect.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT User_ID, Email, Password FROM users WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // User found, verify password
        $row = $result->fetch_assoc();
        // In a real application, you would use password_verify($password, $row['Password'])
        // For demo purposes, we'll just check if they're equal
        if ($password == $row['Password']) { // In production, use password_verify()
            // Password is correct, start a new session
            $_SESSION['user_id'] = $row['User_ID'];
            $_SESSION['email'] = $row['Email'];
            
            // Redirect to homepage
            header("Location: homepage.php");
            exit();
        } else {
            $error = "Email or Password is wrong";
        }
    } else {
        $error = "Email or Password is wrong";
    }
    
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Bites - Login</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-image: url('background.jpg'); /* INSERT BACKGROUND IMAGE HERE */
            background-size: cover;
            background-position: center;
        }
        
        .container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 400px;
            max-width: 100%;
            padding: 30px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo img {
            width: 180px;
            height: auto;
        }
        
        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            color: #555;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        button {
            background-color: #ff5722;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background-color: #e64a19;
        }
        
        .error {
            color: #d32f2f;
            font-size: 14px;
            margin-top: 10px;
            text-align: center;
        }
        
        .signup-link {
            text-align: center;
            margin-top: 20px;
            font-size: 15px;
        }
        
        .signup-link a {
            color: #ff5722;
            text-decoration: none;
        }
        
        .signup-link a:hover {
            text-decoration: underline;
        }
        
        .admin-login {
            position: absolute;
            top: 20px;
            left: 20px;
        }
        
        .admin-login a {
            color: white;
            background-color: rgba(0, 0, 0, 0.6);
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .admin-login a:hover {
            background-color: rgba(0, 0, 0, 0.8);
        }
    </style>
</head>
<body>
    <div class="admin-login">
        <a href="admin_login.php">Admin Login</a>
    </div>
    
    <div class="container">
        <div class="logo">
            <!-- INSERT LOGO IMAGE HERE -->
            <img src="logo.jpeg" alt="Custom Bites Logo">
        </div>
        
        <h2>Welcome to Custom Bites</h2>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <?php if(isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <button type="submit">Login</button>
        </form>
        
        <div class="signup-link">
            Don't have an account? <a href="signup.php">Sign Up</a>
        </div>
    </div>
</body>
</html>