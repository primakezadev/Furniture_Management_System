
<?php
$currentPage = basename($_SERVER["PHP_SELF"]);
?>

<aside class="sidebar">

    <div class="brand">
        <h2>NESTORA</h2>
        <small>ADMIN WORKSPACE</small>
    </div>

    <nav class="sidebar-nav">

        <a href="../admin/dashboard.php">
    <i class="fa-solid fa-house"></i>
    Overview
</a>

      <a href="../furniture/manage_furniture.php"
   class="<?= $currentPage == 'manage_furniture.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-couch"></i> Furniture
</a>


<a href="../admin/manage_customers.php"
   class="<?= $currentPage == 'manage_customers.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-users"></i> Customers
</a>
       <a href="../orders/manage_order.php"
   class="<?= $currentPage == 'manage_order.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-box"></i> Orders
</a>

    </nav>

    <div class="sidebar-note">
        <h3>Considered care.</h3>
        <p>A little care behind every piece, and every order.</p>
        <a href="../customer/dashboard.php">View storefront ↗</a>
    </div>

    <div class="sidebar-bottom">

        <a href="change_password.php">
            <i class="fa-solid fa-key"></i>
            Change password
        </a>

        <a href="logout.php">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            Log out
        </a>

    </div>

</aside>
