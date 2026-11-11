
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$pageTitle = "Manage Customers";
$message = "";
$messageType = "success";

// CSRF protection
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Handle update and delete
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $message = "Invalid request. Please refresh the page.";
        $messageType = "error";

    } else {

        $action = $_POST["action"] ?? "";

        // UPDATE CUSTOMER
        if ($action === "update") {

            $customerId = (int)($_POST["customer_id"] ?? 0);
            $fullName = trim($_POST["full_name"] ?? "");
            $email = trim($_POST["email"] ?? "");
            $phone = trim($_POST["phone"] ?? "");
            $address = trim($_POST["address"] ?? "");

            if ($customerId <= 0 || $fullName === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = "Please provide a valid name and email.";
                $messageType = "error";
            } else {
                try {
                    $stmt = $conn->prepare("
                        UPDATE customers
                        SET full_name = ?,
                            email = ?,
                            phone = ?,
                            address = ?
                        WHERE customer_id = ?
                    ");

                    $stmt->execute([
                        $fullName,
                        $email,
                        $phone ?: null,
                        $address ?: null,
                        $customerId
                    ]);

                    $message = "Customer information updated successfully.";

                } catch (PDOException $e) {
                    if ($e->getCode() === "23000") {
                        $message = "This email address is already registered.";
                    } else {
                        $message = "Unable to update customer information.";
                    }

                    $messageType = "error";
                }
            }
        }

        // DELETE CUSTOMER
        elseif ($action === "delete") {

            $customerId = (int)($_POST["customer_id"] ?? 0);

            try {
                // Check whether customer has orders
                $check = $conn->prepare("
                    SELECT COUNT(*)
                    FROM orders
                    WHERE customer_id = ?
                ");

                $check->execute([$customerId]);
                $orderCount = (int)$check->fetchColumn();

                if ($customerId <= 0) {
                    $message = "Invalid customer selected.";
                    $messageType = "error";

                } elseif ($orderCount > 0) {
                    $message = "This customer cannot be deleted because they have existing orders.";
                    $messageType = "error";

                } else {
                    $delete = $conn->prepare("
                        DELETE FROM customers
                        WHERE customer_id = ?
                    ");

                    $delete->execute([$customerId]);

                    $message = "Customer deleted successfully.";
                }

            } catch (PDOException $e) {
                $message = "Unable to delete this customer.";
                $messageType = "error";
            }
        }
    }
}

// SEARCH
$search = trim($_GET["search"] ?? "");

// PAGINATION
$limit = 5;
$page = max(1, (int)($_GET["page"] ?? 1));

$where = "";
$params = [];

if ($search !== "") {
    $where = "
        WHERE full_name LIKE ?
        OR email LIKE ?
        OR phone LIKE ?
    ";

    $keyword = "%$search%";
    $params = [$keyword, $keyword, $keyword];
}

// Total customers matching search
$countStmt = $conn->prepare("
    SELECT COUNT(*)
    FROM customers
    $where
");

$countStmt->execute($params);
$totalCustomers = (int)$countStmt->fetchColumn();

$totalPages = max(1, (int)ceil($totalCustomers / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

// Fetch customers
$stmt = $conn->prepare("
    SELECT customer_id, full_name, email, phone, address, created_at
    FROM customers
    $where
    ORDER BY customer_id DESC
    LIMIT $limit OFFSET $offset
");

$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Customer selected for editing
$editCustomer = null;

if (isset($_GET["edit"])) {
    $editId = (int)$_GET["edit"];

    $editStmt = $conn->prepare("
        SELECT customer_id, full_name, email, phone, address
        FROM customers
        WHERE customer_id = ?
    ");

    $editStmt->execute([$editId]);
    $editCustomer = $editStmt->fetch(PDO::FETCH_ASSOC);
}

require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/sidebar.php";
?>

<main class="main-content customer-management">

    <style>
        .customer-management {
            flex: 1;
            padding: 35px;
            min-width: 0;
        }

        .customer-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .customer-heading h1 {
            font-family: 'Playfair Display', serif;
            color: #294D3E;
            font-size: 29px;
            font-weight: 500;
        }

        .customer-heading p {
            color: #85877E;
            font-size: 13px;
            margin-top: 7px;
        }

        .customer-summary {
            background: white;
            padding: 22px;
            border-radius: 7px;
            border: 1px solid #E5E5DE;
            margin-bottom: 24px;
        }

        .customer-summary span {
            color: #85877E;
            font-size: 12px;
        }

        .customer-summary h2 {
            color: #294D3E;
            margin-top: 9px;
            font-size: 25px;
        }

        .customer-panel {
            background: white;
            padding: 22px;
            border-radius: 7px;
            border: 1px solid #E5E5DE;
            margin-bottom: 25px;
            overflow-x: auto;
        }

        .customer-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .customer-toolbar h3 {
            color: #294D3E;
            font-size: 16px;
        }

        .customer-search {
            display: flex;
            gap: 8px;
        }

        .customer-search input {
            padding: 10px;
            border: 1px solid #DDDED5;
            border-radius: 4px;
            outline: none;
            min-width: 220px;
        }

        .customer-search button,
        .customer-btn {
            background: #294D3E;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
        }

        .customer-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        .customer-table th {
            background: #F0F0E9;
            color: #535A53;
            padding: 13px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
        }

        .customer-table td {
            padding: 14px 13px;
            border-bottom: 1px solid #EEEEEA;
            font-size: 12px;
            color: #535A53;
        }

        .customer-table tr:hover {
            background: #FAFAF7;
        }

        .action-links {
            display: flex;
            gap: 12px;
        }

        .edit-link {
            color: #294D3E;
            text-decoration: none;
            font-weight: 600;
        }

        .delete-btn {
            border: none;
            background: none;
            color: #B64B43;
            cursor: pointer;
            font-size: 12px;
        }

        .customer-alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .customer-alert.success {
            background: #E7F1E9;
            color: #294D3E;
        }

        .customer-alert.error {
            background: #F9E8E6;
            color: #A33E35;
        }

        .customer-edit-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .customer-edit-form label {
            display: block;
            font-size: 12px;
            margin-bottom: 7px;
            color: #535A53;
        }

        .customer-edit-form input,
        .customer-edit-form textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #DDDED5;
            border-radius: 4px;
            font-family: inherit;
            font-size: 13px;
        }

        .customer-edit-form textarea {
            resize: vertical;
        }

        .form-full {
            grid-column: 1 / -1;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 22px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            text-decoration: none;
            border: 1px solid #DDDED5;
            color: #294D3E;
            border-radius: 4px;
            font-size: 12px;
        }

        .pagination .current {
            background: #294D3E;
            color: white;
        }

        .customer-empty {
            text-align: center;
            padding: 30px;
            color: #85877E;
        }

        @media(max-width: 700px) {
            .customer-management {
                padding: 20px 12px;
            }

            .customer-edit-form {
                grid-template-columns: 1fr;
            }

            .form-full {
                grid-column: auto;
            }
        }
    </style>

    <div class="customer-heading">
        <div>
            <h1>Manage Customers</h1>
            <p>View and manage your registered NESTORA customers.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="customer-alert <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="customer-summary">
        <span>Total registered customers</span>
        <h2><?= $totalCustomers ?></h2>
    </div>

    <?php if ($editCustomer): ?>
        <div class="customer-panel">
            <div class="customer-toolbar">
                <h3>Edit Customer</h3>
                <a href="manage_customers.php" class="customer-btn">Cancel</a>
            </div>

            <form method="POST" class="customer-edit-form">

                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                <input type="hidden" name="action" value="update">

                <input type="hidden" name="customer_id"
                       value="<?= (int)$editCustomer["customer_id"] ?>">

                <div>
                    <label>Full Name</label>
                    <input type="text" name="full_name" required
                           value="<?= htmlspecialchars($editCustomer["full_name"]) ?>">
                </div>

                <div>
                    <label>Email Address</label>
                    <input type="email" name="email" required
                           value="<?= htmlspecialchars($editCustomer["email"]) ?>">
                </div>

                <div>
                    <label>Phone Number</label>
                    <input type="text" name="phone"
                           value="<?= htmlspecialchars($editCustomer["phone"] ?? "") ?>">
                </div>

                <div>
                    <label>Address</label>
                    <textarea name="address" rows="2"><?= htmlspecialchars($editCustomer["address"] ?? "") ?></textarea>
                </div>

                <div class="form-full">
                    <button type="submit" class="customer-btn">
                        Save Changes
                    </button>
                </div>

            </form>
        </div>
    <?php endif; ?>

    <div class="customer-panel">

        <div class="customer-toolbar">
            <h3>Customer Records</h3>

            <form method="GET" class="customer-search">
                <input type="text" name="search"
                       placeholder="Search customers..."
                       value="<?= htmlspecialchars($search) ?>">

                <button type="submit">Search</button>

                <?php if ($search !== ""): ?>
                    <a href="manage_customers.php" class="customer-btn">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <table class="customer-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

            <?php if (count($customers) > 0): ?>

                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?= (int)$customer["customer_id"] ?></td>

                        <td><?= htmlspecialchars($customer["full_name"]) ?></td>

                        <td><?= htmlspecialchars($customer["email"]) ?></td>

                        <td><?= htmlspecialchars($customer["phone"] ?? "—") ?></td>

                        <td>
                            <?= htmlspecialchars(date("d M Y", strtotime($customer["created_at"]))) ?>
                        </td>

                        <td>
                            <div class="action-links">

                                <a class="edit-link"
                                   href="manage_customers.php?edit=<?= (int)$customer["customer_id"] ?>">
                                    Edit
                                </a>

                                <form method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this customer?');">

                                    <input type="hidden" name="csrf_token"
                                           value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                                    <input type="hidden" name="action" value="delete">

                                    <input type="hidden" name="customer_id"
                                           value="<?= (int)$customer["customer_id"] ?>">

                                    <button type="submit" class="delete-btn">
                                        Delete
                                    </button>

                                </form>

                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="6" class="customer-empty">
                        No customers found.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                    <?php
                    $query = http_build_query([
                        "page" => $i,
                        "search" => $search
                    ]);
                    ?>

                    <?php if ($i === $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="manage_customers.php?<?= htmlspecialchars($query) ?>">
                            <?= $i ?>
                        </a>
                    <?php endif; ?>

                <?php endfor; ?>

            </div>
        <?php endif; ?>

    </div>

</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
