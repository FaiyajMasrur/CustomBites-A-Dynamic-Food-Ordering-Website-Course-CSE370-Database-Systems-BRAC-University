<?php
// Start session
session_start();

// Include database connection
include 'dbconnect.php';

// Initialize variables
$error = "";
$success = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $address = $_POST['address'];
    $contact = $_POST['contact'];
    
    // Validate input
    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT Email FROM users WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already exists. Please use a different email.";
        } else {
            // In a real application, you would hash the password
            // $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (First_name, Last_name, Email, Password, Address, Contact) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $first_name, $last_name, $email, $password, $address, $contact);
            
            if ($stmt->execute()) {
                // Get the user ID of the new user
                $user_id = $conn->insert_id;
                
                // Insert into Customer table
                $stmt = $conn->prepare("INSERT INTO customers (Customer_ID) VALUES (?)");
                $stmt->bind_param("i", $user_id);
                
                if ($stmt->execute()) {
                    $success = "Registration successful! You can now login.";
                    // Redirect to login page after 2 seconds
                    header("refresh:2;url=index.php");
                } else {
                    $error = "Error creating customer profile: " . $stmt->error;
                }
            } else {
                $error = "Error registering user: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Bites - Sign Up</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: url('background.jpg'); /* INSERT BACKGROUND IMAGE HERE */
            background-size: cover;
            background-position: center;
            padding: 40px 0;
        }
        
        .container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 500px;
            max-width: 100%;
            padding: 30px;
            margin: 20px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo img {
            width: 150px;
            height: auto;
        }
        
        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 25px;
            font-size: 26px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            color: #555;
            margin-bottom: 6px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .name-row {
            display: flex;
            gap: 15px;
        }
        
        .name-row .form-group {
            flex: 1;
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
            margin: 10px 0;
            text-align: center;
        }
        
        .success {
            color: #388e3c;
            font-size: 14px;
            margin: 10px 0;
            text-align: center;
        }
        
        .login-link {
            text-align: center;
            margin-top: 15px;
            font-size: 15px;
        }
        
        .login-link a {
            color: #ff5722;
            text-decoration: none;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <!-- INSERT LOGO IMAGE HERE -->
            <img src="logo.jpeg" alt="Custom Bites Logo">
        </div>
        
        <h2>Create an Account</h2>
        
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="name-row">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address">
            </div>
            
            <div class="form-group">
                <label for="contact">Contact Number</label>
                <input type="tel" id="contact" name="contact">
            </div>
            
            <button type="submit">Sign Up</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="index.php">Login</a>
        </div>
    </div>
</body>
</html>