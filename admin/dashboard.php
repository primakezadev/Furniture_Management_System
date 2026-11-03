<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

// Get admin username
$username = htmlspecialchars(
    $_SESSION["admin_username"],
    ENT_QUOTES,
    "UTF-8"
);

// Count furniture
$furnitureQuery = $conn->query(
    "SELECT COUNT(*) FROM furniture"
);
$totalFurniture = $furnitureQuery->fetchColumn();

// Count customers
$customerQuery = $conn->query(
    "SELECT COUNT(*) FROM customers"
);
$totalCustomers = $customerQuery->fetchColumn();

// Count orders
$orderQuery = $conn->query(
    "SELECT COUNT(*) FROM orders"
);
$totalOrders = $orderQuery->fetchColumn();

$pageTitle = "Admin Dashboard";
// Get the five most recent orders
$recentOrdersQuery = $conn->query("
    SELECT
        orders.order_id,
        customers.full_name,
        orders.order_date,
        orders.total_amount,
        orders.status
    FROM orders
    INNER JOIN customers
        ON orders.customer_id = customers.customer_id
    ORDER BY orders.order_date DESC
    LIMIT 5
");

$recentOrders = $recentOrdersQuery->fetchAll(PDO::FETCH_ASSOC);
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
            <p><?php echo $totalFurniture; ?></p>
        </div>

        <div class="dashboard-card">
            <h3>Total Customers</h3>
            <p><?php echo $totalCustomers; ?></p>
        </div>

        <div class="dashboard-card">
            <h3>Total Orders</h3>
            <p><?php echo $totalOrders; ?></p>
        </div>

    </div>
    <br><br>

<section class="recent-orders">

    <h2>Recent Orders</h2>

    <br>

    <div class="table-container">

        <table>

            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Order Date</th>
                    <th>Amount (RWF)</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>

                <?php if (count($recentOrders) > 0): ?>

                    <?php foreach ($recentOrders as $order): ?>

                        <tr>
                            <td>
                                <?= htmlspecialchars($order["order_id"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($order["full_name"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($order["order_date"]) ?>
                            </td>

                            <td>
                                <?= number_format((float)$order["total_amount"], 0) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($order["status"]) ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5">No orders have been placed yet.</td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>

</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>