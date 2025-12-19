
<?php
// Start the session
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the appropriate page based on where the logout was initiated
if(isset($_POST['admin_logout'])) {
    // Redirect to admin login if admin is logging out
    header("Location: admin_login.php");
} else {
    // Default redirect to main index page for regular users
    header("Location: index.php");
}
exit();
?>