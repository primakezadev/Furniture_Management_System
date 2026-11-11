
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Check whether a customer is logged in
$isLoggedIn = isset($_SESSION['customer_id']);

$customerName = $isLoggedIn
    ? ($_SESSION['customer_name'] ?? 'Customer')
    : 'Guest';

$customerId = $isLoggedIn
    ? (int) $_SESSION['customer_id']
    : null;

$currentPage = basename($_SERVER['PHP_SELF']);

$customerName = $_SESSION['customer_name'] ?? 'Customer';


$currentPage = basename($_SERVER['PHP_SELF']);

// Identify room category (Dining or Living)
$roomCategory = trim($_GET['room_category'] ?? '');

if (!in_array($roomCategory, ['Dining', 'Living'], true)) {
    $roomCategory = '';
}

// Add furniture to session cart
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add_to_cart'])
) {
        // Guests must sign in before adding furniture to the bag
    if (!$isLoggedIn) {
        $_SESSION['login_message'] =
            "Please sign in to add furniture to your bag.";

        header("Location: ../login.php");
        exit;
    }
    $furnitureId = (int) $_POST['furniture_id'];

    $check = $conn->prepare(
        "SELECT furniture_id, quantity FROM furniture WHERE furniture_id = ?"
    );

    $check->execute([$furnitureId]);
    $product = $check->fetch(PDO::FETCH_ASSOC);

    if ($product && (int)$product['quantity'] > 0) {

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $currentQuantity = $_SESSION['cart'][$furnitureId] ?? 0;

        if ($currentQuantity < (int)$product['quantity']) {
            $_SESSION['cart'][$furnitureId] = $currentQuantity + 1;
            $_SESSION['cart_message'] = "Furniture added to your bag.";
        } else {
            $_SESSION['cart_message'] = "Available stock limit reached.";
        }

    } else {
        $_SESSION['cart_message'] = "This furniture is out of stock.";
    }

    header(
        "Location: " . $currentPage . "?" . http_build_query($_GET)
    );
    exit;
}

// Read filters
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$material = trim($_GET['material'] ?? '');

$maxPrice = isset($_GET['max_price'])
    ? (float)$_GET['max_price']
    : null;

$inStock = isset($_GET['in_stock']);
$sort = $_GET['sort'] ?? 'featured';

// Get available furniture categories
$categories = $conn->query(
    "SELECT category, COUNT(*) AS total
     FROM furniture
     WHERE category IS NOT NULL AND category != ''
     GROUP BY category
     ORDER BY category"
)->fetchAll(PDO::FETCH_ASSOC);

// Get available materials
$materials = $conn->query(
    "SELECT DISTINCT material
     FROM furniture
     WHERE material IS NOT NULL AND material != ''
     ORDER BY material"
)->fetchAll(PDO::FETCH_COLUMN);

// Find highest furniture price
$highestPrice = (float)$conn->query(
    "SELECT COALESCE(MAX(price), 0) FROM furniture"
)->fetchColumn();

if ($maxPrice === null || $maxPrice <= 0) {
    $maxPrice = $highestPrice;
}

// Build furniture query
$where = [];
$params = [];

// Search furniture
if ($search !== '') {
    $where[] = "(
        furniture_name LIKE :search
        OR description LIKE :search
        OR category LIKE :search
        OR material LIKE :search
    )";

    $params[':search'] = "%$search%";
}

// Filter by furniture type
if ($category !== '') {
    $where[] = "category = :category";
    $params[':category'] = $category;
}

// Filter by room category (Dining or Living)
if ($roomCategory !== '') {
    $where[] = "room_category = :room_category";
    $params[':room_category'] = $roomCategory;
}

// Filter by material
if ($material !== '') {
    $where[] = "material = :material";
    $params[':material'] = $material;
}

// Filter by maximum price
if (isset($_GET['max_price'])) {
    $where[] = "price <= :max_price";
    $params[':max_price'] = $maxPrice;
}

// Filter by stock availability
if ($inStock) {
    $where[] = "quantity > 0";
}

// Main SQL query
$sql = "SELECT * FROM furniture";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

// Sorting
switch ($sort) {

    case 'price_low':
        $sql .= " ORDER BY price ASC";
        break;

    case 'price_high':
        $sql .= " ORDER BY price DESC";
        break;

    case 'name':
        $sql .= " ORDER BY furniture_name ASC";
        break;

    default:
        $sql .= " ORDER BY created_at DESC";
        break;
}

// Pagination
$perPage = 6;
$page = max(1, (int)($_GET['page'] ?? 1));

// Count matching furniture
$countSql = "SELECT COUNT(*) FROM furniture";

if (!empty($where)) {
    $countSql .= " WHERE " . implode(" AND ", $where);
}

$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);

$totalProducts = (int)$countStmt->fetchColumn();

$totalPages = max(
    1,
    (int)ceil($totalProducts / $perPage)
);

$page = min($page, $totalPages);

$offset = ($page - 1) * $perPage;

// Apply pagination
$sql .= " LIMIT $perPage OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$furniture = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cart count
$cartCount = array_sum($_SESSION['cart'] ?? []);

// Preserve filters when changing pages
$queryParams = $_GET;
unset($queryParams['page']);

function pageUrl($pageNumber, $queryParams) {
    $queryParams['page'] = $pageNumber;
    return '?' . http_build_query($queryParams);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Furniture Collection | NESTORA</title>

    <link rel="stylesheet" href="../assets/css/customer.css">
</head>

<body>

<!-- ANNOUNCEMENT BAR -->
<div class="announcement">
    Thoughtfully made. Naturally at home.
    <span>·</span>
    Complimentary delivery on orders over $1,000
</div>

<!-- NAVIGATION -->
<header class="navbar">

    <a href="dashboard.php" class="logo">
        NESTORA
        <small>FURNITURE FOR A CONSIDERED LIFE</small>
    </a>

    <nav>

        <a href="dashboard.php"
           class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            Furniture
        </a>

        <a href="living.php"
           class="<?= $currentPage === 'living.php' ? 'active' : '' ?>">
            Living
        </a>

        <a href="dining.php"
           class="<?= $currentPage === 'dining.php' ? 'active' : '' ?>">
            Dining
        </a>

        <a href="our_story.php">
            Our Story
        </a>

        <a href="order_history.php">
            My Orders
        </a>

    </nav>

  <div class="account">

    <?php if ($isLoggedIn): ?>

        <span class="customer-name">
            ♙ <?= htmlspecialchars($customerName) ?>
        </span>

        <a href="logout.php">Log out</a>

        <a href="cart.php" class="bag-link">
            ♧ Bag (<?= $cartCount ?>)
        </a>

    <?php else: ?>

        <a href="../login.php">Sign in</a>

        <a href="../register.php">Create account</a>

    <?php endif; ?>

</div>

</header>

<!-- HERO -->
<section class="hero-banner">

    <div class="hero-content">

        <span class="eyebrow">
            THE EVERYDAY, ELEVATED
        </span>

        <?php if ($roomCategory === 'Dining'): ?>

            <h1>Gather around good design.</h1>

            <p>
                Thoughtful pieces for shared meals,
                meaningful moments and everyday living.
            </p>

        <?php elseif ($roomCategory === 'Living'): ?>

            <h1>Make room for living.</h1>

            <p>
                Comfortable furniture and timeless designs
                for the spaces where life happens.
            </p>

        <?php else: ?>

            <h1>Room for a slower life.</h1>

            <p>
                Natural textures. Enduring design.
                Find your next forever piece.
            </p>

        <?php endif; ?>

    </div>

    <div class="hero-image"></div>

</section>

<main class="shop-layout">

    <!-- SIDEBAR FILTERS -->
    <aside class="filters">

        <div class="filter-heading">

            <strong>Filter by</strong>

            <a href="<?= htmlspecialchars($currentPage) ?>">
                Reset all
            </a>

        </div>

        <form
            method="GET"
            action="<?= htmlspecialchars($currentPage) ?>"
            id="filterForm"
        >

            <!-- Preserve room category -->
            <?php if ($roomCategory !== ''): ?>
                <input
                    type="hidden"
                    name="room_category"
                    value="<?= htmlspecialchars($roomCategory) ?>"
                >
            <?php endif; ?>

            <input
                type="hidden"
                name="search"
                value="<?= htmlspecialchars($search) ?>"
            >

            <input
                type="hidden"
                name="sort"
                value="<?= htmlspecialchars($sort) ?>"
            >

            <div class="filter-group">

                <h4>CATEGORY</h4>

                <label class="filter-option">

                    <input
                        type="radio"
                        name="category"
                        value=""
                        <?= $category === '' ? 'checked' : '' ?>
                    >

                    <span>All furniture</span>

                </label>

                <?php foreach ($categories as $cat): ?>

                    <label class="filter-option">

                        <input
                            type="radio"
                            name="category"
                            value="<?= htmlspecialchars($cat['category']) ?>"
                            <?= $category === $cat['category'] ? 'checked' : '' ?>
                        >

                        <span>
                            <?= htmlspecialchars($cat['category']) ?>
                        </span>

                        <small>
                            <?= (int)$cat['total'] ?>
                        </small>

                    </label>

                <?php endforeach; ?>

            </div>

            <div class="filter-group">

                <h4>PRICE RANGE</h4>

                <input
                    type="range"
                    name="max_price"
                    min="0"
                    max="<?= max(1, ceil($highestPrice)) ?>"
                    value="<?= min($maxPrice, max(1, ceil($highestPrice))) ?>"
                    id="priceRange"
                    <?= $highestPrice <= 0 ? 'disabled' : '' ?>
                >

                <div class="price-values">

                    <span>0 RWF</span>

                    <span id="priceValue">
                        <?= number_format($maxPrice, 0) ?> RWF
                    </span>

                </div>

            </div>

            <div class="filter-group">

                <h4>MATERIAL</h4>

                <?php foreach ($materials as $mat): ?>

                    <label class="filter-option">

                        <input
                            type="radio"
                            name="material"
                            value="<?= htmlspecialchars($mat) ?>"
                            <?= $material === $mat ? 'checked' : '' ?>
                        >

                        <span>
                            <?= htmlspecialchars($mat) ?>
                        </span>

                    </label>

                <?php endforeach; ?>

                <label class="filter-option stock-option">

                    <input
                        type="checkbox"
                        name="in_stock"
                        value="1"
                        <?= $inStock ? 'checked' : '' ?>
                    >

                    <span>In stock only</span>

                </label>

            </div>

            <button type="submit" class="apply-filters">
                Apply filters
            </button>

        </form>

        <p class="filter-help">
            Looking for something specific?
            Search by name or material.
        </p>

    </aside>

    <!-- PRODUCTS -->
    <section class="products-section">

        <div class="search-toolbar">

            <!-- SEARCH FORM -->
            <form
                method="GET"
                action="<?= htmlspecialchars($currentPage) ?>"
                class="search-form"
            >

                <span>⌕</span>

                <input
                    type="search"
                    name="search"
                    placeholder="Search furniture, materials, or collections"
                    value="<?= htmlspecialchars($search) ?>"
                >

                <?php if ($roomCategory !== ''): ?>

                    <input
                        type="hidden"
                        name="room_category"
                        value="<?= htmlspecialchars($roomCategory) ?>"
                    >

                <?php endif; ?>

                <?php if ($category !== ''): ?>

                    <input
                        type="hidden"
                        name="category"
                        value="<?= htmlspecialchars($category) ?>"
                    >

                <?php endif; ?>

                <?php if ($material !== ''): ?>

                    <input
                        type="hidden"
                        name="material"
                        value="<?= htmlspecialchars($material) ?>"
                    >

                <?php endif; ?>

                <?php if (isset($_GET['max_price'])): ?>

                    <input
                        type="hidden"
                        name="max_price"
                        value="<?= htmlspecialchars((string)$maxPrice) ?>"
                    >

                <?php endif; ?>

                <?php if ($inStock): ?>

                    <input
                        type="hidden"
                        name="in_stock"
                        value="1"
                    >

                <?php endif; ?>

                <button type="submit">
                    Search
                </button>

            </form>

            <!-- SORT FORM -->
            <form
                method="GET"
                action="<?= htmlspecialchars($currentPage) ?>"
            >

                <?php if ($roomCategory !== ''): ?>

                    <input
                        type="hidden"
                        name="room_category"
                        value="<?= htmlspecialchars($roomCategory) ?>"
                    >

                <?php endif; ?>

                <input
                    type="hidden"
                    name="search"
                    value="<?= htmlspecialchars($search) ?>"
                >

                <input
                    type="hidden"
                    name="category"
                    value="<?= htmlspecialchars($category) ?>"
                >

                <input
                    type="hidden"
                    name="material"
                    value="<?= htmlspecialchars($material) ?>"
                >

                <?php if (isset($_GET['max_price'])): ?>

                    <input
                        type="hidden"
                        name="max_price"
                        value="<?= htmlspecialchars((string)$maxPrice) ?>"
                    >

                <?php endif; ?>

                <?php if ($inStock): ?>

                    <input
                        type="hidden"
                        name="in_stock"
                        value="1"
                    >

                <?php endif; ?>

                <select
                    name="sort"
                    onchange="this.form.submit()"
                >

                    <option
                        value="featured"
                        <?= $sort === 'featured' ? 'selected' : '' ?>
                    >
                        Sort: Featured
                    </option>

                    <option
                        value="price_low"
                        <?= $sort === 'price_low' ? 'selected' : '' ?>
                    >
                        Price: Low to high
                    </option>

                    <option
                        value="price_high"
                        <?= $sort === 'price_high' ? 'selected' : '' ?>
                    >
                        Price: High to low
                    </option>

                    <option
                        value="name"
                        <?= $sort === 'name' ? 'selected' : '' ?>
                    >
                        Name: A to Z
                    </option>

                </select>

            </form>

        </div>

        <!-- RESULTS INFORMATION -->
        <div class="results-info">

            <span>
                <?= $totalProducts ?> available pieces for your home
            </span>

            <span>In stock +</span>

        </div>

        <!-- CART MESSAGE -->
        <?php if (isset($_SESSION['cart_message'])): ?>

            <div class="cart-message">

                <?= htmlspecialchars($_SESSION['cart_message']) ?>

            </div>

            <?php unset($_SESSION['cart_message']); ?>

        <?php endif; ?>

        <!-- FURNITURE LIST -->
        <?php if (!empty($furniture)): ?>

            <div class="furniture-grid">

                <?php foreach ($furniture as $item): ?>

                    <article class="furniture-card">

                        <div class="furniture-image">

                            <?php if (!empty($item['image'])): ?>

                                <img
                                    src="../assets/images/furniture/<?= htmlspecialchars($item['image']) ?>"
                                    alt="<?= htmlspecialchars($item['furniture_name']) ?>"
                                >

                            <?php else: ?>

                                <div class="no-image">
                                    No image available
                                </div>

                            <?php endif; ?>

                            <?php if ((int)$item['quantity'] > 0): ?>

                                <form
                                    method="POST"
                                    action="<?= htmlspecialchars($currentPage) ?>?<?= htmlspecialchars(http_build_query($_GET)) ?>"
                                    class="add-cart-form"
                                >

                                    <input
                                        type="hidden"
                                        name="furniture_id"
                                        value="<?= (int)$item['furniture_id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="add_to_cart"
                                        class="add-cart"
                                        title="Add to bag"
                                    >
                                        +
                                    </button>

                                </form>

                            <?php else: ?>

                                <span class="out-stock">
                                    Out of stock
                                </span>

                            <?php endif; ?>

                        </div>

                        <div class="product-info">

                            <div class="product-title">

                                <h3>
                                    <?= htmlspecialchars($item['furniture_name']) ?>
                                </h3>

                                <strong>
                                    <?= number_format((float)$item['price'], 0) ?> RWF
                                </strong>

                            </div>

                            <p>

                                <?= htmlspecialchars($item['material'] ?? '') ?>

                                <?= !empty($item['category'])
                                    ? ' · ' . htmlspecialchars($item['category'])
                                    : '' ?>

                            </p>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-state">

                <h3>No furniture found</h3>

                <p>
                    Try changing your search or filters.
                </p>

                <a href="<?= htmlspecialchars($currentPage) ?>">
                    View all furniture
                </a>

            </div>

        <?php endif; ?>

        <!-- PAGINATION -->
        <?php if ($totalPages > 1): ?>

            <div class="pagination">

                <span>

                    Showing <?= $offset + 1 ?>–<?= min(
                        $offset + $perPage,
                        $totalProducts
                    ) ?>

                    of <?= $totalProducts ?> available pieces

                </span>

                <div class="page-links">

                    <?php if ($page > 1): ?>

                        <a href="<?= pageUrl($page - 1, $queryParams) ?>">
                            ‹
                        </a>

                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                        <a
                            href="<?= pageUrl($i, $queryParams) ?>"
                            class="<?= $page === $i ? 'current-page' : '' ?>"
                        >
                            <?= $i ?>
                        </a>

                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>

                        <a href="<?= pageUrl($page + 1, $queryParams) ?>">
                            ›
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>

    </section>

</main>

<!-- FOOTER -->
<footer id="our-story">
    © 2026 NESTORA · Furniture for a considered life.
</footer>

<script>

const priceRange = document.getElementById('priceRange');
const priceValue = document.getElementById('priceValue');

if (priceRange && priceValue) {

    priceRange.addEventListener('input', function () {

        priceValue.textContent =
            Number(this.value).toLocaleString() + ' RWF';

    });

}

</script>

</body>
</html>
