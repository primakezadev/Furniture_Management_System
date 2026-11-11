
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$username = htmlspecialchars(
    $_SESSION["admin_username"] ?? "Administrator",
    ENT_QUOTES,
    "UTF-8"
);

// Statistics
$totalFurniture = (int)$conn->query(
    "SELECT COUNT(*) FROM furniture"
)->fetchColumn();

$totalCustomers = (int)$conn->query(
    "SELECT COUNT(*) FROM customers"
)->fetchColumn();

$totalOrders = (int)$conn->query(
    "SELECT COUNT(*) FROM orders"
)->fetchColumn();

$pendingOrders = (int)$conn->query(
    "SELECT COUNT(*) FROM orders WHERE status = 'Pending'"
)->fetchColumn();

$lowStock = (int)$conn->query(
    "SELECT COUNT(*) FROM furniture WHERE quantity <= 5"
)->fetchColumn();

// Sales this month
$salesQuery = $conn->query("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM orders
    WHERE MONTH(order_date) = MONTH(CURRENT_DATE())
    AND YEAR(order_date) = YEAR(CURRENT_DATE())
    AND status = 'Completed'
");

$monthlySales = (float)$salesQuery->fetchColumn();

// Last six months sales
$salesChartQuery = $conn->query("
    SELECT
        DATE_FORMAT(order_date, '%Y-%m') AS month_key,
        DATE_FORMAT(order_date, '%b') AS month_name,
        SUM(total_amount) AS sales
    FROM orders
    WHERE order_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 5 MONTH)
    AND status = 'Completed'
    GROUP BY month_key, month_name
    ORDER BY month_key
");

$salesRows = $salesChartQuery->fetchAll(PDO::FETCH_ASSOC);

$salesByMonth = [];

foreach ($salesRows as $row) {
    $salesByMonth[$row["month_key"]] = (float)$row["sales"];
}

$chartLabels = [];
$chartValues = [];

for ($i = 5; $i >= 0; $i--) {
    $date = new DateTime("first day of -$i month");
    $key = $date->format("Y-m");

    $chartLabels[] = $date->format("M");
    $chartValues[] = $salesByMonth[$key] ?? 0;
}

$maxSales = max($chartValues);
$maxSales = $maxSales > 0 ? $maxSales : 1;

// Recent orders
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

$pageTitle = "Admin Overview";

require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/sidebar.php";
?>

<main class="main-content">

    <header class="topbar">
        <div class="breadcrumb">
            Workspace <span>/</span> Overview
        </div>

        <div class="admin-profile">
            <i class="fa-regular fa-bell"></i>

            <div class="profile-circle">
                <?= strtoupper(substr($username, 0, 1)) ?>
            </div>

            <div>
                <strong><?= $username ?></strong>
                <small>Administrator</small>
            </div>
        </div>
    </header>

    <section class="welcome-section">

        <div>
            <p class="date-label">
                <?= strtoupper(date("l, F d, Y")) ?>
            </p>

            <h1>Good morning, <?= $username ?>.</h1>

            <p>Here's what's happening at NESTORA today.</p>
        </div>

        <a href="../furniture/manage_furniture.php" class="register-btn">
            Register furniture <i class="fa-solid fa-plus"></i>
        </a>

    </section>

    <!-- Statistics -->
    <section class="stats-grid">

        <div class="stat-card">
            <div class="stat-heading">
                <span>Total furniture</span>
                <i class="fa-solid fa-couch"></i>
            </div>

            <h2><?= $totalFurniture ?></h2>
            <small>Items in your collection</small>
        </div>

        <div class="stat-card">
            <div class="stat-heading">
                <span>Customers</span>
                <i class="fa-solid fa-users"></i>
            </div>

            <h2><?= $totalCustomers ?></h2>
            <small>Registered customers</small>
        </div>

        <div class="stat-card">
            <div class="stat-heading">
                <span>Incoming orders</span>
                <i class="fa-solid fa-box"></i>
            </div>

            <h2><?= $pendingOrders ?></h2>
            <small>Awaiting confirmation</small>
        </div>

        <div class="stat-card">
            <div class="stat-heading">
                <span>Sales this month</span>
                <i class="fa-solid fa-chart-line"></i>
            </div>

            <h2><?= number_format($monthlySales, 0) ?> RWF</h2>
            <small>Completed orders this month</small>
        </div>

    </section>

    <!-- Sales and Attention -->
    <section class="overview-grid">

        <div class="sales-card">

            <div class="section-heading">
                <div>
                    <h3>Sales overview</h3>
                    <p>Completed orders · Last 6 months</p>
                </div>

                <span><?= date("Y") ?></span>
            </div>

            <div class="chart">

                <div class="chart-y-labels">
                    <span><?= number_format($maxSales, 0) ?></span>
                    <span><?= number_format($maxSales * .66, 0) ?></span>
                    <span><?= number_format($maxSales * .33, 0) ?></span>
                    <span>0</span>
                </div>

                <div class="chart-bars">

                    <?php foreach ($chartValues as $index => $value): ?>

                        <?php
                        $height = $value > 0
                            ? max(3, ($value / $maxSales) * 100)
                            : 2;
                        ?>

                        <div class="chart-column">

                            <div class="bar"
                                 style="height: <?= $height ?>%">
                            </div>

                            <small><?= $chartLabels[$index] ?></small>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>

        <div class="attention-card">

            <h2>A little attention.</h2>

            <div class="attention-item">
                <i class="fa-solid fa-box-open"></i>

                <div>
                    <strong><?= $pendingOrders ?> orders need confirmation</strong>
                    <small>Review incoming customer orders</small>
                </div>

                <a href="../orders/manage_order.php">↗</a>
            </div>

            <div class="attention-item">
                <i class="fa-solid fa-triangle-exclamation"></i>

                <div>
                    <strong><?= $lowStock ?> pieces are running low</strong>
                    <small>Furniture with 5 or fewer items in stock</small>
                </div>

                <a href="../furniture/manage_furniture.php">↗</a>
            </div>

            <a href="../orders/manage_orders.php" class="review-btn">
                Review incoming orders
            </a>

        </div>

    </section>

    <!-- Recent Orders -->
    <section class="recent-orders">

        <div class="orders-heading">
            <h3>Recent orders</h3>
            <a href="manage_orders.php">View all orders →</a>
        </div>

        <div class="table-container">

            <table>

                <thead>
                    <tr>
                        <th>ORDER</th>
                        <th>CUSTOMER</th>
                        <th>PLACED</th>
                        <th>TOTAL</th>
                        <th>STATUS</th>
                        <th>ACTION</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($recentOrders) > 0): ?>

                        <?php foreach ($recentOrders as $order): ?>

                            <tr>
                                <td>
                                    #FF-<?= htmlspecialchars($order["order_id"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($order["full_name"]) ?>
                                </td>

                                <td>
                                    <?= date(
                                        "M d, Y",
                                        strtotime($order["order_date"])
                                    ) ?>
                                </td>

                                <td>
                                    <?= number_format(
                                        (float)$order["total_amount"],
                                        0
                                    ) ?> RWF
                                </td>

                                <td>
                                    <span class="status-badge <?= strtolower(str_replace(' ', '-', $order["status"])) ?>">
                                        <?= htmlspecialchars($order["status"]) ?>
                                    </span>
                                </td>

                                <td>
                                    <a class="view-link" href="manage_orders.php">
                                        View order →
                                    </a>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="empty-orders">
                                No orders have been placed yet.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

    <footer class="page-footer">
        <span>Form & Field · Furniture information management</span>
        <span>© <?= date("Y") ?></span>
    </footer>

</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
