<?php
$currentPage = basename($_SERVER["PHP_SELF"]);
?>

<aside class="sidebar">

    <h2>Furniture MS</h2>

    <a href="dashboard.php"
       class="<?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
        Dashboard
    </a>

    <a href="add_furniture.php"
       class="<?= $currentPage == 'add_furniture.php' ? 'active' : '' ?>">
        Add Furniture
    </a>

    <a href="manage_furniture.php"
       class="<?= $currentPage == 'manage_furniture.php' ? 'active' : '' ?>">
        Manage Furniture
    </a>

    <a href="manage_customers.php"
       class="<?= $currentPage == 'manage_customers.php' ? 'active' : '' ?>">
        Manage Customers
    </a>

    <a href="manage_orders.php"
       class="<?= $currentPage == 'manage_orders.php' ? 'active' : '' ?>">
        Manage Orders
    </a>

    <a href="change_password.php"
       class="<?= $currentPage == 'change_password.php' ? 'active' : '' ?>">
        Change Password
    </a>

    <a href="logout.php">Logout</a>

</aside>