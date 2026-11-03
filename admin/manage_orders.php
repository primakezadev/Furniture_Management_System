
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$pageTitle = "Manage Orders";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$allowedStatuses = ["Pending", "Confirmed", "Completed", "Cancelled"];

$error = "";

// Update order status
if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_status"])) {

    if (!hash_equals(
        $_SESSION["csrf_token"],
        $_POST["csrf_token"] ?? ""
    )) {
        die("Invalid request. Please refresh the page.");
    }

    $orderId = filter_var(
        $_POST["order_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    $newStatus = $_POST["status"] ?? "";

    if (!$orderId || !in_array($newStatus, $allowedStatuses, true)) {
        $error = "Invalid order or status.";
    } else {
        $update = $conn->prepare("
            UPDATE orders
            SET status = :status
            WHERE order_id = :order_id
        ");

        $update->execute([
            ":status" => $newStatus,
            ":order_id" => $orderId
        ]);

        header("Location: manage_orders.php?updated=1");
        exit;
    }
}

// Search
$search = trim($_GET["search"] ?? "");

// Pagination
$limit = 8;
$page = max(1, (int)($_GET["page"] ?? 1));
$offset = ($page - 1) * $limit;

// Count orders
$countSql = "
    SELECT COUNT(*)
    FROM orders o
    INNER JOIN customers c
        ON o.customer_id = c.customer_id
    WHERE (
        CAST(o.order_id AS CHAR) LIKE :search
        OR c.full_name LIKE :search
        OR c.email LIKE :search
    )
";

$countQuery = $conn->prepare($countSql);
$countQuery->execute([
    ":search" => "%$search%"
]);

$totalOrders = (int)$countQuery->fetchColumn();
$totalPages = max(1, (int)ceil($totalOrders / $limit));

// Get orders
$ordersQuery = $conn->prepare("
    SELECT
        o.order_id,
        o.customer_id,
        o.order_date,
        o.total_amount,
        o.status,
        c.full_name,
        c.email,
        c.phone
    FROM orders o
    INNER JOIN customers c
        ON o.customer_id = c.customer_id
    WHERE (
        CAST(o.order_id AS CHAR) LIKE :search
        OR c.full_name LIKE :search
        OR c.email LIKE :search
    )
    ORDER BY o.order_date DESC
    LIMIT $limit OFFSET $offset
");

$ordersQuery->execute([
    ":search" => "%$search%"
]);

$orders = $ordersQuery->fetchAll(PDO::FETCH_ASSOC);

// View individual order
$selectedOrder = null;
$orderItems = [];

if (isset($_GET["view"])) {

    $viewId = filter_var($_GET["view"], FILTER_VALIDATE_INT);

    if ($viewId) {

        $detailQuery = $conn->prepare("
            SELECT
                o.*,
                c.full_name,
                c.email,
                c.phone,
                c.address
            FROM orders o
            INNER JOIN customers c
                ON o.customer_id = c.customer_id
            WHERE o.order_id = ?
        ");

        $detailQuery->execute([$viewId]);

        $selectedOrder = $detailQuery->fetch(PDO::FETCH_ASSOC);

        if ($selectedOrder) {

            $itemsQuery = $conn->prepare("
                SELECT
                    oi.quantity,
                    oi.unit_price,
                    f.furniture_name
                FROM order_items oi
                INNER JOIN furniture f
                    ON oi.furniture_id = f.furniture_id
                WHERE oi.order_id = ?
            ");

            $itemsQuery->execute([$viewId]);

            $orderItems = $itemsQuery->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/sidebar.php";
?>

<main class="main-content">

    <header class="topbar">
        <div class="breadcrumb">
            Workspace <span>/</span> Orders
        </div>

        <div class="admin-profile">
            <i class="fa-regular fa-bell"></i>

            <div class="profile-circle">
                <?= strtoupper(substr(
                    $_SESSION["admin_username"] ?? "A",
                    0,
                    1
                )) ?>
            </div>

            <div>
                <strong>
                    <?= htmlspecialchars($_SESSION["admin_username"] ?? "Administrator") ?>
                </strong>
                <small>Administrator</small>
            </div>
        </div>
    </header>

    <section class="orders-page-heading">

        <div>
            <p class="date-label">ORDER MANAGEMENT</p>
            <h1>Manage orders</h1>
            <p>Track and manage customer purchases.</p>
        </div>

        <div class="orders-total">
            <strong><?= $totalOrders ?></strong>
            <small>Total orders</small>
        </div>

    </section>

    <?php if (isset($_GET["updated"])): ?>
        <div class="success-message">
            Order status updated successfully.
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error-message">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Search -->
    <section class="orders-panel">

        <form method="GET" class="orders-search">

            <div>
                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    name="search"
                    placeholder="Search order ID, customer or email..."
                    value="<?= htmlspecialchars($search) ?>"
                >
            </div>

            <button type="submit">Search</button>

            <?php if ($search !== ""): ?>
                <a href="manage_orders.php">Clear</a>
            <?php endif; ?>

        </form>

        <!-- Orders table -->
        <div class="table-container">

            <table class="orders-management-table">

                <thead>
                    <tr>
                        <th>ORDER</th>
                        <th>CUSTOMER</th>
                        <th>DATE PLACED</th>
                        <th>TOTAL</th>
                        <th>STATUS</th>
                        <th>ACTION</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($orders) > 0): ?>

                        <?php foreach ($orders as $order): ?>

                            <tr>

                                <td>
                                    #FF-<?= (int)$order["order_id"] ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($order["full_name"]) ?>
                                    </strong>
                                    <small class="customer-email">
                                        <?= htmlspecialchars($order["email"]) ?>
                                    </small>
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
                                    <span class="status-badge <?= strtolower($order["status"]) ?>">
                                        <?= htmlspecialchars($order["status"]) ?>
                                    </span>
                                </td>

                                <td>
                                    <a
                                        class="view-link"
                                        href="manage_orders.php?view=<?= (int)$order["order_id"] ?>"
                                    >
                                        View details →
                                    </a>
                                </td>

                            </tr>

                            <tr class="order-action-row">
                                <td colspan="6">

                                    <form method="POST" class="status-form">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                                        <input type="hidden"
                                               name="order_id"
                                               value="<?= (int)$order["order_id"] ?>">

                                        <label>Update status:</label>

                                        <select name="status">
                                            <?php foreach ($allowedStatuses as $status): ?>
                                                <option
                                                    value="<?= $status ?>"
                                                    <?= $order["status"] === $status ? "selected" : "" ?>
                                                >
                                                    <?= $status ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <button type="submit" name="update_status">
                                            Save status
                                        </button>

                                    </form>

                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="empty-orders">
                                No orders found.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>

            <div class="orders-pagination">

                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                        ← Previous
                    </a>
                <?php endif; ?>

                <span>
                    Page <?= $page ?> of <?= $totalPages ?>
                </span>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
                        Next →
                    </a>
                <?php endif; ?>

            </div>

        <?php endif; ?>

    </section>

    <!-- Order details -->
    <?php if (isset($_GET["view"])): ?>

        <section class="order-details-panel">

            <?php if ($selectedOrder): ?>

                <div class="orders-heading">
                    <h3>
                        Order #FF-<?= (int)$selectedOrder["order_id"] ?>
                        details
                    </h3>

                    <a href="manage_orders.php">Close ×</a>
                </div>

                <div class="customer-order-info">

                    <div>
                        <small>Customer</small>
                        <strong>
                            <?= htmlspecialchars($selectedOrder["full_name"]) ?>
                        </strong>
                    </div>

                    <div>
                        <small>Email</small>
                        <strong>
                            <?= htmlspecialchars($selectedOrder["email"]) ?>
                        </strong>
                    </div>

                    <div>
                        <small>Phone</small>
                        <strong>
                            <?= htmlspecialchars($selectedOrder["phone"] ?? "Not provided") ?>
                        </strong>
                    </div>

                    <div>
                        <small>Address</small>
                        <strong>
                            <?= htmlspecialchars($selectedOrder["address"] ?? "Not provided") ?>
                        </strong>
                    </div>

                </div>

                <h4 class="items-title">Ordered furniture</h4>

                <div class="table-container">

                    <table>
                        <thead>
                            <tr>
                                <th>FURNITURE</th>
                                <th>QUANTITY</th>
                                <th>UNIT PRICE</th>
                                <th>SUBTOTAL</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($orderItems as $item): ?>

                                <tr>
                                    <td>
                                        <?= htmlspecialchars($item["furniture_name"]) ?>
                                    </td>

                                    <td>
                                        <?= (int)$item["quantity"] ?>
                                    </td>

                                    <td>
                                        <?= number_format((float)$item["unit_price"], 0) ?> RWF
                                    </td>

                                    <td>
                                        <?= number_format(
                                            (int)$item["quantity"] * (float)$item["unit_price"],
                                            0
                                        ) ?> RWF
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        </tbody>
                    </table>

                </div>

                <div class="order-grand-total">
                    Total:
                    <?= number_format((float)$selectedOrder["total_amount"], 0) ?> RWF
                </div>

            <?php else: ?>

                <p>Order not found.</p>

            <?php endif; ?>

        </section>

    <?php endif; ?>

    <footer class="page-footer">
        <span>Form & Field · Furniture information management</span>
        <span>© <?= date("Y") ?></span>
    </footer>

</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
