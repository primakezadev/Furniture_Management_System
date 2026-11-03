
<?php
session_start();

require_once __DIR__ . "/../config/database.php";

if (!isset($_SESSION["customer_id"])) {
    header("Location: ../admin/login.php");
    exit;
}

if (empty($_SESSION["cart"]) || !is_array($_SESSION["cart"])) {
    header("Location: cart.php");
    exit;
}

$customerId = (int)$_SESSION["customer_id"];

if (empty($_SESSION["checkout_csrf"])) {
    $_SESSION["checkout_csrf"] = bin2hex(random_bytes(32));
}

$error = "";
$items = [];
$total = 0;

// Get customer details
$customerQuery = $conn->prepare("
    SELECT full_name, email, phone, address
    FROM customers
    WHERE customer_id = ?
");

$customerQuery->execute([$customerId]);
$customer = $customerQuery->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    session_destroy();
    header("Location: ../admin/login.php");
    exit;
}

// Get cart furniture and verify stock
$cart = $_SESSION["cart"];
$ids = [];

foreach ($cart as $id => $quantity) {
    $id = filter_var($id, FILTER_VALIDATE_INT);
    $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

    if ($id && $quantity && $quantity > 0) {
        $ids[$id] = $quantity;
    }
}

if (empty($ids)) {
    header("Location: cart.php");
    exit;
}

$placeholders = implode(",", array_fill(0, count($ids), "?"));

$productQuery = $conn->prepare("
    SELECT furniture_id, furniture_name, price, quantity, image
    FROM furniture
    WHERE furniture_id IN ($placeholders)
");

$productQuery->execute(array_keys($ids));
$products = $productQuery->fetchAll(PDO::FETCH_ASSOC);

if (count($products) !== count($ids)) {
    $error = "Some furniture items are no longer available. Please review your cart.";
} else {
    foreach ($products as $product) {

        $id = (int)$product["furniture_id"];
        $requested = $ids[$id];
        $stock = (int)$product["quantity"];

        if ($stock < $requested) {
            $error = "Insufficient stock for " .
                $product["furniture_name"] .
                ". Available quantity: " . $stock;

            break;
        }

        $product["cart_quantity"] = $requested;
        $product["subtotal"] = $requested * (float)$product["price"];

        $total += $product["subtotal"];
        $items[] = $product;
    }
}

// Place order
if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["place_order"])) {

    if (!hash_equals(
        $_SESSION["checkout_csrf"],
        $_POST["csrf_token"] ?? ""
    )) {
        die("Invalid request. Please refresh the page.");
    }

    if ($error !== "" || empty($items)) {
        $error = $error ?: "Your cart is empty.";
    } else {

        try {
            $conn->beginTransaction();

            // Lock furniture records and check stock again
            $lockQuery = $conn->prepare("
                SELECT furniture_id, price, quantity
                FROM furniture
                WHERE furniture_id = ?
                FOR UPDATE
            ");

            $orderItems = [];
            $verifiedTotal = 0;

            foreach ($ids as $id => $requestedQuantity) {

                $lockQuery->execute([$id]);
                $product = $lockQuery->fetch(PDO::FETCH_ASSOC);

                if (!$product ||
                    (int)$product["quantity"] < $requestedQuantity) {
                    throw new Exception(
                        "Insufficient stock for one or more furniture items. Please review your cart."
                    );
                }

                $unitPrice = (float)$product["price"];

                $verifiedTotal += $unitPrice * $requestedQuantity;

                $orderItems[] = [
                    "id" => $id,
                    "quantity" => $requestedQuantity,
                    "price" => $unitPrice
                ];
            }

            // Insert order
            $insertOrder = $conn->prepare("
                INSERT INTO orders
                    (customer_id, total_amount, status)
                VALUES
                    (?, ?, 'Pending')
            ");

            $insertOrder->execute([
                $customerId,
                $verifiedTotal
            ]);

            $orderId = (int)$conn->lastInsertId();

            // Insert order items and reduce stock
            $insertItem = $conn->prepare("
                INSERT INTO order_items
                    (order_id, furniture_id, quantity, unit_price)
                VALUES
                    (?, ?, ?, ?)
            ");

            $updateStock = $conn->prepare("
                UPDATE furniture
                SET quantity = quantity - ?
                WHERE furniture_id = ?
                  AND quantity >= ?
            ");

            foreach ($orderItems as $item) {

                $insertItem->execute([
                    $orderId,
                    $item["id"],
                    $item["quantity"],
                    $item["price"]
                ]);

                $updateStock->execute([
                    $item["quantity"],
                    $item["id"],
                    $item["quantity"]
                ]);

                if ($updateStock->rowCount() !== 1) {
                    throw new Exception(
                        "Furniture stock could not be updated."
                    );
                }
            }

            $conn->commit();

            // Clear cart after successful order
            unset($_SESSION["cart"]);

            header("Location: order_success.php?id=" . $orderId);
            exit;

        } catch (Exception $e) {

            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout | Form & Field</title>

    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="stylesheet" href="../assets/css/cart.css">
</head>

<body>

<div class="announcement">
    Thoughtfully made. Naturally at home.
</div>

<header class="navbar">

    <a href="dashboard.php" class="logo">
        FORM & FIELD
        <small>FURNITURE FOR A CONSIDERED LIFE</small>
    </a>

    <nav>
        <a href="dashboard.php">Furniture</a>
        <a href="cart.php">Shopping bag</a>
    </nav>

    <div class="account">
        <?= htmlspecialchars($customer["full_name"]) ?>
    </div>

</header>

<main class="cart-container">

    <div class="cart-heading">
        <div>
            <span class="eyebrow">FINAL STEP</span>
            <h1>Checkout</h1>
            <p>Review your order before confirming.</p>
        </div>

        <a href="cart.php" class="continue-link">
            ← Back to bag
        </a>
    </div>

    <?php if ($error !== ""): ?>

        <div class="error-message">
            <?= htmlspecialchars($error) ?>
            <a href="cart.php">Review your cart</a>
        </div>

    <?php else: ?>

        <div class="cart-content">

            <section class="cart-items">

                <div class="order-details-panel">
                    <h2>Delivery information</h2>

                    <div class="customer-order-info">

                        <div>
                            <small>Full name</small>
                            <strong><?= htmlspecialchars($customer["full_name"]) ?></strong>
                        </div>

                        <div>
                            <small>Email</small>
                            <strong><?= htmlspecialchars($customer["email"]) ?></strong>
                        </div>

                        <div>
                            <small>Phone</small>
                            <strong><?= htmlspecialchars($customer["phone"] ?? "Not provided") ?></strong>
                        </div>

                        <div>
                            <small>Address</small>
                            <strong><?= htmlspecialchars($customer["address"] ?? "Not provided") ?></strong>
                        </div>

                    </div>
                </div>

                <div class="order-details-panel" style="margin-top:20px; padding:20px;">

                    <h2>Payment method</h2>

                    <p style="margin-top:15px; font-size:13px;">
                        <strong>Payment on Delivery</strong>
                    </p>

                    <p style="margin-top:8px; color:#85877E; font-size:12px;">
                        You will pay when your furniture is delivered.
                    </p>

                </div>

                <div class="order-details-panel" style="margin-top:20px; padding:20px;">

                    <h2>Furniture in your order</h2>

                    <?php foreach ($items as $item): ?>

                        <div class="cart-item">

                            <div class="cart-image">

                                <?php if (!empty($item["image"])): ?>

                                    <img
                                        src="../uploads/<?= htmlspecialchars($item["image"]) ?>"
                                        alt="<?= htmlspecialchars($item["furniture_name"]) ?>"
                                    >

                                <?php else: ?>

                                    <div class="no-image">
                                        No image available
                                    </div>

                                <?php endif; ?>

                            </div>

                            <div class="cart-item-info">

                                <h3><?= htmlspecialchars($item["furniture_name"]) ?></h3>

                                <p>
                                    Quantity: <?= (int)$item["cart_quantity"] ?>
                                </p>

                            </div>

                            <strong class="item-subtotal">
                                <?= number_format($item["subtotal"], 0) ?> RWF
                            </strong>

                        </div>

                    <?php endforeach; ?>

                </div>

            </section>

            <aside class="cart-summary">

                <h2>Order summary</h2>

                <div>
                    <span>Items</span>
                    <strong><?= array_sum($ids) ?></strong>
                </div>

                <div>
                    <span>Delivery</span>
                    <span>To be confirmed</span>
                </div>

                <hr>

                <div class="cart-total">
                    <span>Total</span>
                    <strong><?= number_format($total, 0) ?> RWF</strong>
                </div>

                <form method="POST">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION["checkout_csrf"]) ?>"
                    >

                    <button
                        type="submit"
                        name="place_order"
                        class="checkout-button"
                        style="width:100%; border:none; cursor:pointer;"
                    >
                        Confirm and place order
                    </button>

                </form>

                <p>
                    By confirming, you agree to place this order
                    with payment on delivery.
                </p>

            </aside>

        </div>

    <?php endif; ?>

</main>

<footer>
    © <?= date("Y") ?> Form & Field · Furniture for a considered life.
</footer>

</body>
</html>
