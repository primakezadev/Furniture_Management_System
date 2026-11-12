
<?php
session_start();

require_once __DIR__ . "/../config/database.php";

// Ensure customer is logged in
if (!isset($_SESSION["customer_id"])) {
    header("Location: ../admin/login.php");
    exit;
}

$customerId = (int) $_SESSION["customer_id"];

// Get all orders belonging to this customer
$query = $conn->prepare("
    SELECT
        o.order_id,
        o.order_date,
        o.total_amount,
        o.status,
        COUNT(oi.order_item_id) AS total_items
    FROM orders o
    LEFT JOIN order_items oi
        ON o.order_id = oi.order_id
    WHERE o.customer_id = ?
    GROUP BY
        o.order_id,
        o.order_date,
        o.total_amount,
        o.status
    ORDER BY o.order_date DESC
");

$query->execute([$customerId]);
$orders = $query->fetchAll(PDO::FETCH_ASSOC);

// Get furniture details for a selected order
$selectedOrder = null;
$orderItems = [];

if (isset($_GET["id"])) {

    $orderId = filter_var($_GET["id"], FILTER_VALIDATE_INT);

    if ($orderId) {

        // Verify that the order belongs to the logged-in customer
        $orderQuery = $conn->prepare("
            SELECT order_id, order_date, total_amount, status
            FROM orders
            WHERE order_id = ? AND customer_id = ?
        ");

        $orderQuery->execute([$orderId, $customerId]);
        $selectedOrder = $orderQuery->fetch(PDO::FETCH_ASSOC);

        if ($selectedOrder) {

            $itemsQuery = $conn->prepare("
                SELECT
                    f.furniture_name,
                    f.image,
                    oi.quantity,
                    oi.unit_price,
                    (oi.quantity * oi.unit_price) AS subtotal
                FROM order_items oi
                INNER JOIN furniture f
                    ON oi.furniture_id = f.furniture_id
                WHERE oi.order_id = ?
            ");

            $itemsQuery->execute([$orderId]);
            $orderItems = $itemsQuery->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Orders | NESTORA</title>

    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="stylesheet" href="../assets/css/cart.css">
    <link rel="stylesheet" href="../assets/css/order_history.css">
</head>

<body>

<div class="announcement">
    Thoughtfully made. Naturally at home.
</div>

<header class="navbar">

    <a href="dashboard.php" class="logo">
        NESTORA
        <small>FURNITURE FOR A CONSIDERED LIFE</small>
    </a>

    <nav>
        <a href="dashboard.php">Furniture</a>
        <a href="cart.php">Shopping bag</a>
        <a href="order_history.php">My orders</a>
    </nav>

    <div class="account">
        <?= htmlspecialchars($_SESSION["customer_name"] ?? "My Account") ?>
    </div>

</header>

<main class="orders-container">

    <div class="orders-heading">
        <span class="eyebrow">YOUR PURCHASES</span>
        <h1>My Orders</h1>
        <p>View your purchases and keep track of their progress.</p>
    </div>

    <?php if ($selectedOrder): ?>

        <section class="order-details">

            <a href="order_history.php" class="back-link">
                ← Back to my orders
            </a>

            <div class="order-details-heading">
                <div>
                    <h2>
                        Order #FF-<?= (int)$selectedOrder["order_id"] ?>
                    </h2>

                    <p>
                        Placed on
                        <?= date("d M Y, H:i", strtotime($selectedOrder["order_date"])) ?>
                    </p>
                </div>

                <span class="status <?= strtolower($selectedOrder["status"]) ?>">
                    <?= htmlspecialchars($selectedOrder["status"]) ?>
                </span>
            </div>

            <h3>Items in this order</h3>

            <?php foreach ($orderItems as $item): ?>

                <div class="history-item">

                    <div class="history-image">

                        <?php if (!empty($item["image"])): ?>

                            <img
                                src="../assets/images/furniture/<?= htmlspecialchars($item["image"]) ?>"
                                alt="<?= htmlspecialchars($item["furniture_name"]) ?>"
                            >

                        <?php else: ?>

                            <span>No image</span>

                        <?php endif; ?>

                    </div>

                    <div class="history-item-info">

                        <h4>
                            <?= htmlspecialchars($item["furniture_name"]) ?>
                        </h4>

                        <p>
                            Quantity: <?= (int)$item["quantity"] ?>
                        </p>

                        <p>
                            Unit price:
                            <?= number_format((float)$item["unit_price"], 0) ?> RWF
                        </p>

                    </div>

                    <strong>
                        <?= number_format((float)$item["subtotal"], 0) ?> RWF
                    </strong>

                </div>

            <?php endforeach; ?>

            <div class="history-total">
                <span>Order total</span>

                <strong>
                    <?= number_format((float)$selectedOrder["total_amount"], 0) ?> RWF
                </strong>
            </div>

            <p class="payment-note">
                Payment method: Payment on Delivery
            </p>

        </section>

    <?php elseif (isset($_GET["id"])): ?>

        <div class="no-orders">
            <h2>Order not found</h2>
            <p>This order could not be found in your account.</p>
            <a href="order_history.php">Back to my orders</a>
        </div>

    <?php elseif (empty($orders)): ?>

        <div class="no-orders">
            <h2>You haven't placed any orders yet.</h2>
            <p>Your furniture purchases will appear here.</p>

            <a href="dashboard.php">Explore furniture</a>
        </div>

    <?php else: ?>

        <div class="orders-list">

            <?php foreach ($orders as $order): ?>

                <article class="order-card">

                    <div class="order-card-top">

                        <div>
                            <span class="order-label">ORDER NUMBER</span>

                            <h3>
                                #FF-<?= (int)$order["order_id"] ?>
                            </h3>
                        </div>

                        <span class="status <?= strtolower($order["status"]) ?>">
                            <?= htmlspecialchars($order["status"]) ?>
                        </span>

                    </div>

                    <div class="order-card-info">

                        <div>
                            <small>Order date</small>

                            <strong>
                                <?= date("d M Y", strtotime($order["order_date"])) ?>
                            </strong>
                        </div>

                        <div>
                            <small>Items</small>

                            <strong>
                                <?= (int)$order["total_items"] ?>
                            </strong>
                        </div>

                        <div>
                            <small>Total amount</small>

                            <strong>
                                <?= number_format((float)$order["total_amount"], 0) ?> RWF
                            </strong>
                        </div>

                    </div>

                    <div class="order-card-bottom">

                        <span>Payment on Delivery</span>

                        <a href="order_history.php?id=<?= (int)$order["order_id"] ?>">
                            View order details →
                        </a>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

<footer>
    © <?= date("Y") ?> NESTORA · Furniture for a considered life.
</footer>

</body>
</html>
