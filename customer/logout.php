
<?php
session_start();

// Remove customer session information
unset($_SESSION['customer_id']);
unset($_SESSION['customer_name']);

// Destroy the session
session_destroy();

// Redirect to the public furniture dashboard
header("Location: dashboard.php");
exit;
?>
