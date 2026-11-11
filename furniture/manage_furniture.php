
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../admin/login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$pageTitle = "Manage Furniture";
$message = "";
$messageType = "success";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Helper for safe image paths
function furnitureImageUrl($image) {
    $image = basename($image ?? "");

    if ($image === "") {
        return "../assets/images/furniture/placeholder.png";
    }

    return "../assets/images/furniture/" . rawurlencode($image);
}

// ADD FURNITURE
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $message = "Invalid request. Please refresh the page.";
        $messageType = "error";

    } else {

        $action = $_POST["action"] ?? "";

        if ($action === "add") {

            $name = trim($_POST["furniture_name"] ?? "");
            $description = trim($_POST["description"] ?? "");
            $price = $_POST["price"] ?? "";
            $quantity = $_POST["quantity"] ?? "";
            $category = trim($_POST["category"] ?? "");
            $material = trim($_POST["material"] ?? "");
            $roomCategory = trim($_POST["room_category"] ?? "");

            $allowedRooms = ["Living", "Dining"];

            if (
                $name === "" ||
                !is_numeric($price) ||
                (float)$price <= 0 ||
                filter_var($quantity, FILTER_VALIDATE_INT) === false ||
                (int)$quantity < 0 ||
                !in_array($roomCategory, $allowedRooms, true)
            ) {
                $message = "Please provide valid furniture information.";
                $messageType = "error";

            } else {

                $imageName = null;
                $uploadedPath = null;

                try {

                    if (
                        isset($_FILES["image"]) &&
                        $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
                    ) {
                        if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
                            throw new RuntimeException("Image upload failed.");
                        }

                        if ($_FILES["image"]["size"] > 5 * 1024 * 1024) {
                            throw new RuntimeException("Image must be less than 5 MB.");
                        }

                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mime = $finfo->file($_FILES["image"]["tmp_name"]);

                        $extensions = [
                            "image/jpeg" => "jpg",
                            "image/png" => "png",
                            "image/webp" => "webp"
                        ];

                        if (!isset($extensions[$mime])) {
                            throw new RuntimeException("Only JPG, PNG and WEBP images are allowed.");
                        }

                        $imageName = bin2hex(random_bytes(12)) . "." . $extensions[$mime];

                        $uploadDirectory = __DIR__ . "/../assets/images/furniture/";

                        if (!is_dir($uploadDirectory)) {
                            throw new RuntimeException("Furniture image folder was not found.");
                        }

                        $uploadedPath = $uploadDirectory . $imageName;

                        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $uploadedPath)) {
                            throw new RuntimeException("Unable to save furniture image.");
                        }
                    }

                    $stmt = $conn->prepare("
                        INSERT INTO furniture
                        (furniture_name, description, price, quantity, image,
                         category, material, room_category)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $name,
                        $description ?: null,
                        $price,
                        $quantity,
                        $imageName,
                        $category ?: null,
                        $material ?: null,
                        $roomCategory
                    ]);

                    $message = "Furniture added successfully.";

                } catch (Throwable $e) {

                    if ($uploadedPath && file_exists($uploadedPath)) {
                        unlink($uploadedPath);
                    }

                    $message = $e instanceof RuntimeException
                        ? $e->getMessage()
                        : "Unable to add furniture. Please check the information.";

                    $messageType = "error";
                }
            }
        }

        // DELETE FURNITURE
        elseif ($action === "delete") {

            $furnitureId = (int)($_POST["furniture_id"] ?? 0);

            if ($furnitureId <= 0) {
                $message = "Invalid furniture selected.";
                $messageType = "error";

            } else {

                try {

                    // Check whether furniture is used in existing order items
                    $check = $conn->prepare("
                        SELECT COUNT(*)
                        FROM order_items
                        WHERE furniture_id = ?
                    ");

                    $check->execute([$furnitureId]);

                    if ((int)$check->fetchColumn() > 0) {
                        $message = "This furniture cannot be deleted because it is linked to an order.";
                        $messageType = "error";

                    } else {

                        $getImage = $conn->prepare("
                            SELECT image FROM furniture WHERE furniture_id = ?
                        ");

                        $getImage->execute([$furnitureId]);
                        $image = $getImage->fetchColumn();

                        $delete = $conn->prepare("
                            DELETE FROM furniture WHERE furniture_id = ?
                        ");

                        $delete->execute([$furnitureId]);

                        if ($delete->rowCount() > 0) {

                            if ($image) {
                                $imagePath = __DIR__ . "/../assets/images/furniture/" . basename($image);

                                if (is_file($imagePath)) {
                                    unlink($imagePath);
                                }
                            }

                            $message = "Furniture deleted successfully.";

                        } else {
                            $message = "Furniture record was not found.";
                            $messageType = "error";
                        }
                    }

                } catch (PDOException $e) {
                    $message = "Unable to delete this furniture because it may be used by another record.";
                    $messageType = "error";
                }
            }
        }
    }
}

// SEARCH AND FILTER
$search = trim($_GET["search"] ?? "");
$room = $_GET["room"] ?? "";

$conditions = [];
$params = [];

if ($search !== "") {
    $conditions[] = "(furniture_name LIKE ? OR category LIKE ? OR material LIKE ?)";
    $keyword = "%$search%";
    $params = [$keyword, $keyword, $keyword];
}

if (in_array($room, ["Living", "Dining"], true)) {
    $conditions[] = "room_category = ?";
    $params[] = $room;
} else {
    $room = "";
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// PAGINATION
$limit = 6;
$page = max(1, (int)($_GET["page"] ?? 1));

$countStmt = $conn->prepare("
    SELECT COUNT(*) FROM furniture $where
");

$countStmt->execute($params);
$totalFurniture = (int)$countStmt->fetchColumn();

$totalPages = max(1, (int)ceil($totalFurniture / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

// FETCH FURNITURE
$stmt = $conn->prepare("
    SELECT *
    FROM furniture
    $where
    ORDER BY furniture_id DESC
    LIMIT $limit OFFSET $offset
");

$stmt->execute($params);
$furniture = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/sidebar.php";
?>

<main class="main-content furniture-management">

<style>
.furniture-management {
    flex: 1;
    min-width: 0;
    padding: 35px;
}

.furniture-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
}

.furniture-heading h1 {
    font-family: 'Playfair Display', serif;
    font-size: 29px;
    font-weight: 500;
    color: #294D3E;
}

.furniture-heading p {
    margin-top: 7px;
    font-size: 13px;
    color: #85877E;
}

.furniture-card {
    background: white;
    border: 1px solid #E5E5DE;
    border-radius: 7px;
    padding: 22px;
    margin-bottom: 24px;
}

.furniture-summary {
    color: #85877E;
    font-size: 12px;
}

.furniture-summary strong {
    display: block;
    font-size: 25px;
    color: #294D3E;
    margin-top: 8px;
}

.furniture-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
}

.furniture-toolbar h3 {
    font-size: 16px;
    color: #294D3E;
}

.furniture-search {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.furniture-search input,
.furniture-search select,
.furniture-form input,
.furniture-form select,
.furniture-form textarea {
    padding: 10px;
    border: 1px solid #DDDED5;
    border-radius: 4px;
    font-family: inherit;
    font-size: 13px;
}

.furniture-btn {
    background: #294D3E;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    font-size: 12px;
}

.furniture-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}

.furniture-table th {
    background: #F0F0E9;
    color: #535A53;
    padding: 13px;
    font-size: 11px;
    text-align: left;
}

.furniture-table td {
    padding: 12px;
    border-bottom: 1px solid #EEEEEA;
    color: #535A53;
    font-size: 12px;
}

.furniture-table img {
    width: 58px;
    height: 53px;
    object-fit: cover;
    border-radius: 4px;
}

.stock-low {
    color: #B64B43;
    font-weight: 600;
}

.stock-good {
    color: #294D3E;
    font-weight: 600;
}

.furniture-actions {
    display: flex;
    gap: 12px;
}

.furniture-edit {
    color: #294D3E;
    text-decoration: none;
    font-weight: 600;
}

.furniture-delete {
    border: none;
    background: none;
    color: #B64B43;
    cursor: pointer;
    font-size: 12px;
}

.furniture-alert {
    padding: 12px 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    font-size: 13px;
}

.furniture-alert.success {
    background: #E7F1E9;
    color: #294D3E;
}

.furniture-alert.error {
    background: #F9E8E6;
    color: #A33E35;
}

.furniture-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.furniture-form label {
    display: block;
    margin-bottom: 7px;
    color: #535A53;
    font-size: 12px;
}

.furniture-form input,
.furniture-form select,
.furniture-form textarea {
    width: 100%;
}

.furniture-full {
    grid-column: 1 / -1;
}

.furniture-pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 22px;
}

.furniture-pagination a,
.furniture-pagination span {
    padding: 8px 12px;
    border: 1px solid #DDDED5;
    border-radius: 4px;
    text-decoration: none;
    color: #294D3E;
    font-size: 12px;
}

.furniture-pagination .current {
    background: #294D3E;
    color: white;
}

@media(max-width: 750px) {
    .furniture-management {
        padding: 20px 12px;
    }

    .furniture-form {
        grid-template-columns: 1fr;
    }

    .furniture-full {
        grid-column: auto;
    }
}
</style>

<div class="furniture-heading">
    <div>
        <h1>Manage Furniture</h1>
        <p>Manage your furniture collection, categories and available stock.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="furniture-alert <?= htmlspecialchars($messageType) ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="furniture-card">
    <div class="furniture-summary">
        Total furniture records
        <strong><?= $totalFurniture ?></strong>
    </div>
</div>

<!-- ADD FURNITURE FORM -->
<div class="furniture-card">

    <div class="furniture-toolbar">
        <h3>Add New Furniture</h3>
    </div>

    <form method="POST" enctype="multipart/form-data" class="furniture-form">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

        <input type="hidden" name="action" value="add">

        <div>
            <label>Furniture Name</label>
            <input type="text" name="furniture_name" required maxlength="150">
        </div>

        <div>
            <label>Price (RWF)</label>
            <input type="number" name="price" min="0.01" step="0.01" required>
        </div>

        <div>
            <label>Quantity</label>
            <input type="number" name="quantity" min="0" step="1" required>
        </div>

        <div>
            <label>Furniture Type</label>
            <select name="category">
                <option value="">Select type</option>
                <option value="Seating">Seating</option>
                <option value="Tables">Tables</option>
                <option value="Storage">Storage</option>
            </select>
        </div>

        <div>
            <label>Room Category</label>
            <select name="room_category" required>
                <option value="">Select room</option>
                <option value="Living">Living</option>
                <option value="Dining">Dining</option>
            </select>
        </div>

        <div>
            <label>Material</label>
            <input type="text" name="material" maxlength="100">
        </div>

        <div class="furniture-full">
            <label>Description</label>
            <textarea name="description" rows="3"></textarea>
        </div>

        <div class="furniture-full">
            <label>Furniture Image (JPG, PNG or WEBP, max 5 MB)</label>
            <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        </div>

        <div class="furniture-full">
            <button type="submit" class="furniture-btn">Add Furniture</button>
        </div>

    </form>
</div>

<!-- FURNITURE LIST -->
<div class="furniture-card">

    <div class="furniture-toolbar">
        <h3>Furniture Collection</h3>

        <form method="GET" class="furniture-search">

            <input type="text" name="search"
                   placeholder="Search furniture..."
                   value="<?= htmlspecialchars($search) ?>">

            <select name="room">
                <option value="">All Rooms</option>
                <option value="Living" <?= $room === "Living" ? "selected" : "" ?>>Living</option>
                <option value="Dining" <?= $room === "Dining" ? "selected" : "" ?>>Dining</option>
            </select>

            <button type="submit" class="furniture-btn">Search</button>

            <?php if ($search !== "" || $room !== ""): ?>
                <a href="manage_furniture.php" class="furniture-btn">Clear</a>
            <?php endif; ?>

        </form>
    </div>

    <div style="overflow-x:auto;">
        <table class="furniture-table">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Furniture</th>
                    <th>Type</th>
                    <th>Room</th>
                    <th>Material</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($furniture): ?>

                <?php foreach ($furniture as $item): ?>

                    <tr>
                        <td><?= (int)$item["furniture_id"] ?></td>

                        <td>
                            <?php if (!empty($item["image"])): ?>
                                <img src="<?= htmlspecialchars(furnitureImageUrl($item["image"])) ?>"
                                     alt="<?= htmlspecialchars($item["furniture_name"]) ?>">
                            <?php else: ?>
                                No image
                            <?php endif; ?>
                        </td>

                        <td><?= htmlspecialchars($item["furniture_name"]) ?></td>

                        <td><?= htmlspecialchars($item["category"] ?? "—") ?></td>

                        <td><?= htmlspecialchars($item["room_category"] ?? "—") ?></td>

                        <td><?= htmlspecialchars($item["material"] ?? "—") ?></td>

                        <td><?= number_format((float)$item["price"], 0) ?> RWF</td>

                        <td class="<?= (int)$item["quantity"] <= 5 ? "stock-low" : "stock-good" ?>">
                            <?= (int)$item["quantity"] ?>
                        </td>

                        <td>
                            <div class="furniture-actions">

                                <a class="furniture-edit"
                                   href="edit_furniture.php?id=<?= (int)$item["furniture_id"] ?>">
                                    Edit
                                </a>

                                <form method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this furniture?');">

                                    <input type="hidden" name="csrf_token"
                                           value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                                    <input type="hidden" name="action" value="delete">

                                    <input type="hidden" name="furniture_id"
                                           value="<?= (int)$item["furniture_id"] ?>">

                                    <button type="submit" class="furniture-delete">
                                        Delete
                                    </button>

                                </form>

                            </div>
                        </td>
                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="9" style="text-align:center;padding:30px;">
                        No furniture found.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>

        <div class="furniture-pagination">

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                <?php
                $query = http_build_query([
                    "page" => $i,
                    "search" => $search,
                    "room" => $room
                ]);
                ?>

                <?php if ($i === $page): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="manage_furniture.php?<?= htmlspecialchars($query) ?>">
                        <?= $i ?>
                    </a>
                <?php endif; ?>

            <?php endfor; ?>

        </div>

    <?php endif; ?>

</div>

</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
