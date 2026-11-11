
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../admin/login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$pageTitle = "Edit Furniture";
$message = "";
$messageType = "error";

$furnitureId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$furnitureId || $furnitureId <= 0) {
    header("Location: manage_furniture.php");
    exit;
}

// Get furniture record
$stmt = $conn->prepare("
    SELECT *
    FROM furniture
    WHERE furniture_id = ?
");

$stmt->execute([$furnitureId]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    header("Location: manage_furniture.php");
    exit;
}

// CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// UPDATE FURNITURE
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $message = "Invalid request. Please refresh the page.";

    } else {

        $name = trim($_POST["furniture_name"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $price = $_POST["price"] ?? "";
        $quantity = $_POST["quantity"] ?? "";
        $category = trim($_POST["category"] ?? "");
        $material = trim($_POST["material"] ?? "");
        $roomCategory = trim($_POST["room_category"] ?? "");

        $allowedRooms = ["Living", "Dining"];
        $allowedCategories = ["Seating", "Tables", "Storage"];

        if (
            $name === "" ||
            !is_numeric($price) ||
            (float)$price <= 0 ||
            filter_var($quantity, FILTER_VALIDATE_INT) === false ||
            (int)$quantity < 0 ||
            !in_array($roomCategory, $allowedRooms, true) ||
            ($category !== "" && !in_array($category, $allowedCategories, true))
        ) {
            $message = "Please enter valid furniture information.";

        } else {

            $imageName = $item["image"];
            $uploadedPath = null;
            $oldImage = $item["image"];

            try {

                // Check whether a new image was selected
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

                    $uploadedPath = $uploadDirectory . $imageName;

                    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $uploadedPath)) {
                        throw new RuntimeException("Unable to save the new image.");
                    }
                }

                $update = $conn->prepare("
                    UPDATE furniture
                    SET furniture_name = ?,
                        description = ?,
                        price = ?,
                        quantity = ?,
                        image = ?,
                        category = ?,
                        material = ?,
                        room_category = ?
                    WHERE furniture_id = ?
                ");

                $update->execute([
                    $name,
                    $description ?: null,
                    $price,
                    $quantity,
                    $imageName,
                    $category ?: null,
                    $material ?: null,
                    $roomCategory,
                    $furnitureId
                ]);

                // Remove previous image only after successful database update
                if (
                    $uploadedPath &&
                    $oldImage &&
                    basename($oldImage) !== $imageName
                ) {
                    $oldImagePath = __DIR__ . "/../assets/images/furniture/" . basename($oldImage);

                    if (is_file($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                // Reload updated record
                $stmt->execute([$furnitureId]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);

                $message = "Furniture updated successfully.";
                $messageType = "success";

            } catch (Throwable $e) {

                if ($uploadedPath && is_file($uploadedPath)) {
                    unlink($uploadedPath);
                }

                $message = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : "Unable to update furniture. Please try again.";
            }
        }
    }
}

require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/sidebar.php";
?>

<main class="main-content edit-furniture">

<style>
.edit-furniture {
    flex: 1;
    min-width: 0;
    padding: 35px;
}

.edit-heading {
    margin-bottom: 25px;
}

.edit-heading h1 {
    font-family: 'Playfair Display', serif;
    color: #294D3E;
    font-size: 29px;
    font-weight: 500;
}

.edit-heading p {
    margin-top: 7px;
    color: #85877E;
    font-size: 13px;
}

.edit-card {
    background: white;
    border: 1px solid #E5E5DE;
    border-radius: 7px;
    padding: 25px;
    max-width: 850px;
}

.edit-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.edit-form label {
    display: block;
    margin-bottom: 7px;
    font-size: 12px;
    color: #535A53;
}

.edit-form input,
.edit-form select,
.edit-form textarea {
    width: 100%;
    padding: 11px;
    border: 1px solid #DDDED5;
    border-radius: 4px;
    font-family: inherit;
    font-size: 13px;
}

.edit-form textarea {
    resize: vertical;
}

.edit-full {
    grid-column: 1 / -1;
}

.edit-image {
    width: 120px;
    height: 100px;
    object-fit: cover;
    border-radius: 5px;
    margin-bottom: 12px;
    display: block;
}

.edit-buttons {
    display: flex;
    gap: 12px;
    margin-top: 10px;
}

.edit-btn {
    padding: 11px 17px;
    border: none;
    border-radius: 4px;
    background: #294D3E;
    color: white;
    cursor: pointer;
    text-decoration: none;
    font-size: 12px;
}

.edit-cancel {
    background: #E8E5DB;
    color: #294D3E;
}

.edit-alert {
    padding: 12px 15px;
    margin-bottom: 20px;
    border-radius: 4px;
    font-size: 13px;
}

.edit-alert.success {
    background: #E7F1E9;
    color: #294D3E;
}

.edit-alert.error {
    background: #F9E8E6;
    color: #A33E35;
}

@media(max-width: 650px) {
    .edit-furniture {
        padding: 20px 12px;
    }

    .edit-form {
        grid-template-columns: 1fr;
    }

    .edit-full {
        grid-column: auto;
    }
}
</style>

<div class="edit-heading">
    <h1>Edit Furniture</h1>
    <p>Update the details of your furniture collection.</p>
</div>

<?php if ($message): ?>
    <div class="edit-alert <?= htmlspecialchars($messageType) ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="edit-card">

    <form method="POST" enctype="multipart/form-data" class="edit-form">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

        <div>
            <label>Furniture Name</label>
            <input type="text" name="furniture_name"
                   maxlength="150" required
                   value="<?= htmlspecialchars($item["furniture_name"]) ?>">
        </div>

        <div>
            <label>Price (RWF)</label>
            <input type="number" name="price"
                   min="0.01" step="0.01" required
                   value="<?= htmlspecialchars($item["price"]) ?>">
        </div>

        <div>
            <label>Quantity</label>
            <input type="number" name="quantity"
                   min="0" step="1" required
                   value="<?= (int)$item["quantity"] ?>">
        </div>

        <div>
            <label>Furniture Type</label>
            <select name="category">
                <option value="">Select type</option>

                <?php foreach (["Seating", "Tables", "Storage"] as $category): ?>
                    <option value="<?= $category ?>"
                        <?= ($item["category"] ?? "") === $category ? "selected" : "" ?>>
                        <?= $category ?>
                    </option>
                <?php endforeach; ?>

            </select>
        </div>

        <div>
            <label>Room Category</label>
            <select name="room_category" required>
                <option value="">Select room</option>
                <option value="Living"
                    <?= $item["room_category"] === "Living" ? "selected" : "" ?>>
                    Living
                </option>
                <option value="Dining"
                    <?= $item["room_category"] === "Dining" ? "selected" : "" ?>>
                    Dining
                </option>
            </select>
        </div>

        <div>
            <label>Material</label>
            <input type="text" name="material" maxlength="100"
                   value="<?= htmlspecialchars($item["material"] ?? "") ?>">
        </div>

        <div class="edit-full">
            <label>Description</label>
            <textarea name="description" rows="4"><?= htmlspecialchars($item["description"] ?? "") ?></textarea>
        </div>

        <div class="edit-full">
            <label>Current Furniture Image</label>

            <?php if (!empty($item["image"])): ?>
                <img class="edit-image"
                     src="../assets/images/furniture/<?= rawurlencode(basename($item["image"])) ?>"
                     alt="Current furniture image">
            <?php else: ?>
                <p>No image uploaded.</p>
            <?php endif; ?>

            <label>Choose a New Image (optional)</label>
            <input type="file" name="image"
                   accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            <small>Leave empty to keep the current image. Maximum 5 MB.</small>
        </div>

        <div class="edit-full edit-buttons">
            <button type="submit" class="edit-btn">Save Changes</button>

            <a href="manage_furniture.php" class="edit-btn edit-cancel">
                Back to Furniture
            </a>
        </div>

    </form>

</div>

</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
