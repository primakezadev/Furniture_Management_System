<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$username = htmlspecialchars(
    $_SESSION["admin_username"],
    ENT_QUOTES,
    "UTF-8"
);

$pageTitle = "Admin Dashboard";
?>

<?php require_once __DIR__ . "/../includes/header.php"; ?>

<?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

<main class="main-content">

    <div class="topbar">
        <h2>Admin Dashboard</h2>
        <p>Welcome, <?php echo $username; ?></p>
    </div>

    <h2>Overview</h2>
    <br>

    <div class="dashboard-cards">

        <div class="dashboard-card">
            <h3>Total Furniture</h3>
            <p>0</p>
        </div>

        <div class="dashboard-card">
            <h3>Total Customers</h3>
            <p>0</p>
        </div>

        <div class="dashboard-card">
            <h3>Total Orders</h3>
            <p>0</p>
        </div>

    </div>

</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>