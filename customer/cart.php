
<?php
session_start();

require_once __DIR__ . "/../config/database.php";

if (!isset($_SESSION["customer_id"])) {
    header("Location: ../admin/login.php");
    exit;
}

if (!isset($_SESSION["cart"]) || !is_array($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

if (empty($_SESSION["cart_csrf"])) {
    $_SESSION["cart_csrf"] = bin2hex(random_bytes(32));
}

$message = "";
$cart = &$_SESSION["cart"];

// Remove an item
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals(
        $_SESSION["cart_csrf"],
        $_POST["csrf_token"] ?? ""
    )) {
        die("Invalid request. Please refresh the page.");
    }

    if (isset($_POST["remove_item"])) {
        $id = (int)$_POST["furniture_id"];
        unset($cart[$id]);

        header("Location: cart.php");
        exit;
    }

    // Update quantities
    if (isset($_POST["update_cart"])) {

        foreach ($_POST["quantities"] ?? [] as $id => $quantity) {

            $id = (int)$id;
            $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

            if (!isset($cart[$id])) {
                continue;
            }

            if ($quantity === false || $quantity < 1) {
                continue;
            }

            $check = $conn->prepare("
                SELECT quantity
                FROM furniture
                WHERE furniture_id = ?
            ");
            $check->execute([$id]);

            $stock = $check->fetchColumn();

            if ($stock === false) {
                unset($cart[$id]);
                continue;
            }

            $cart[$id] = min($quantity, (int)$stock);
        }

        header("Location: cart.php");
        exit;
    }
}

// Get cart furniture from database
$items = [];
$total = 0;

if (!empty($cart)) {

    $ids = array_map("intval", array_keys($cart));
    $placeholders = implode(",", array_fill(0, count($ids), "?"));

    $query = $conn->prepare("
        SELECT furniture_id, furniture_name, price, quantity, image
        FROM furniture
        WHERE furniture_id IN ($placeholders)
    ");

    $query->execute($ids);
    $products = $query->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $product) {

        $id = (int)$product["furniture_id"];
        $requestedQuantity = (int)$cart[$id];
        $stock = (int)$product["quantity"];

        if ($stock <= 0) {
            unset($cart[$id]);
            continue;
        }

        $quantity = min($requestedQuantity, $stock);
        $cart[$id] = $quantity;

        $subtotal = $quantity * (float)$product["price"];
        $product["cart_quantity"] = $quantity;
        $product["subtotal"] = $subtotal;

        $total += $subtotal;
        $items[] = $product;
    }
}

$cartCount = array_sum($cart);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Your Bag | Form & Field</title>

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
        <a href="dashboard.php?category=Living">Living</a>
        <a href="dashboard.php?category=Dining">Dining</a>
    </nav>

    <div class="account">
        <span><?= htmlspecialchars($_SESSION["customer_name"] ?? "Customer") ?></span>
        <a href="dashboard.php">Continue shopping</a>
        <a href="logout.php">Log out</a>
    </div>

</header>

<main class="cart-container">

    <div class="cart-heading">
        <div>
            <span class="eyebrow">YOUR SELECTION</span>
            <h1>Your shopping bag</h1>
            <p><?= $cartCount ?> item(s) in your bag</p>
        </div>

        <a href="dashboard.php" class="continue-link">
            ← Continue shopping
        </a>
    </div>

    <?php if (empty($items)): ?>

        <div class="empty-cart">
            <h2>Your bag is waiting.</h2>
            <p>You haven't added any furniture yet.</p>
            <a href="dashboard.php">Explore furniture</a>
        </div>

    <?php else: ?>

        <form method="POST" action="cart.php">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION["cart_csrf"]) ?>"
            >

            <div class="cart-content">

                <section class="cart-items">

                    <?php foreach ($items as $item): ?>

                        <article class="cart-item">

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

                                <h3>
                                    <?= htmlspecialchars($item["furniture_name"]) ?>
                                </h3>

                                <p>
                                    <?= number_format((float)$item["price"], 0) ?> RWF
                                    per piece
                                </p>

                                <label>
                                    Quantity

                                    <input
                                        type="number"
                                        name="quantities[<?= (int)$item["furniture_id"] ?>]"
                                        min="1"
                                        max="<?= (int)$item["quantity"] ?>"
                                        value="<?= (int)$item["cart_quantity"] ?>"
                                    >
                                </label>

                                <button
                                    type="submit"
                                    name="remove_item"
                                    value="1"
                                    formaction="cart.php"
                                    formmethod="POST"
                                    onclick="this.form.furniture_id.value='<?= (int)$item["furniture_id"] ?>'"
                                    class="remove-button"
                                >
                                    Remove
                                </button>

                            </div>

                            <strong class="item-subtotal">
                                <?= number_format($item["subtotal"], 0) ?> RWF
                            </strong>

                        </article>

                    <?php endforeach; ?>

                    <button
                        type="submit"
                        name="update_cart"
                        class="update-cart-button"
                    >
                        Update quantities
                    </button>

                    <input type="hidden" name="furniture_id" value="">

                </section>

                <aside class="cart-summary">

                    <h2>Order summary</h2>

                    <div>
                        <span>Subtotal</span>
                        <strong><?= number_format($total, 0) ?> RWF</strong>
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

                    <a href="checkout.php" class="checkout-button">
                        Proceed to checkout →
                    </a>

                    <p>Review your order before confirming your purchase.</p>

                </aside>

            </div>

        </form>

    <?php endif; ?>

</main>

<footer>
    © <?= date("Y") ?> Form & Field · Furniture for a considered life.
</footer>

</body>
</html>
