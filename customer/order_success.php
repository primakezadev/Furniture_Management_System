
<?php
session_start();

if (!isset($_SESSION["customer_id"])) {
    header("Location: ../admin/login.php");
    exit;
}

$orderId = filter_var(
    $_GET["id"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$orderId) {
    header("Location: dashboard.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$query = $conn->prepare("
    SELECT order_id, order_date, total_amount, status
    FROM orders
    WHERE order_id = ? AND customer_id = ?
");

$query->execute([
    $orderId,
    $_SESSION["customer_id"]
]);

$order = $query->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed | Form & Field</title>
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="stylesheet" href="../assets/css/cart.css">
</head>

<body>

<main class="cart-container">

    <div class="empty-cart">

        <h1>Thank you for your order!</h1>

        <p>Your order has been successfully placed.</p>

        <h2 style="margin-top:25px;">
            Order #FF-<?= (int)$order["order_id"] ?>
        </h2>

        <p style="margin-top:15px;">
            Total:
            <strong>
                <?= number_format((float)$order["total_amount"], 0) ?> RWF
            </strong>
        </p>

        <p>
            Status:
            <?= htmlspecialchars($order["status"]) ?>
        </p>

        <p>
            Payment method: Payment on Delivery
        </p>

        <a href="dashboard.php">
            Continue shopping
        </a>

    </div>

</main>

</body>
</html>
