
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$pageTitle = "Change Password";
$message = "";
$messageType = "";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $csrfToken = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $csrfToken)) {
        $message = "Invalid request. Please refresh the page.";
        $messageType = "error";

    } else {

        $currentPassword = $_POST["current_password"] ?? "";
        $newPassword = $_POST["new_password"] ?? "";
        $confirmPassword = $_POST["confirm_password"] ?? "";

        if (
            $currentPassword === "" ||
            $newPassword === "" ||
            $confirmPassword === ""
        ) {
            $message = "Please fill in all password fields.";
            $messageType = "error";

        } elseif (strlen($newPassword) < 8) {
            $message = "Your new password must contain at least 8 characters.";
            $messageType = "error";

        } elseif ($newPassword !== $confirmPassword) {
            $message = "The new password and confirmation do not match.";
            $messageType = "error";

        } elseif ($currentPassword === $newPassword) {
            $message = "Your new password must be different from your current password.";
            $messageType = "error";

        } else {

            // Get current administrator password
            $stmt = $conn->prepare("
                SELECT password
                FROM admins
                WHERE admin_id = ?
            ");

            $stmt->execute([$_SESSION["admin_id"]]);

            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin || !password_verify($currentPassword, $admin["password"])) {

                $message = "Your current password is incorrect.";
                $messageType = "error";

            } else {

                // Hash the new password
                $hashedPassword = password_hash(
                    $newPassword,
                    PASSWORD_DEFAULT
                );

                // Update administrator password
                $update = $conn->prepare("
                    UPDATE admins
                    SET password = ?
                    WHERE admin_id = ?
                ");

                $update->execute([
                    $hashedPassword,
                    $_SESSION["admin_id"]
                ]);

                // Generate a new CSRF token
                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

                $message = "Your password has been changed successfully.";
                $messageType = "success";
            }
        }
    }
}

require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/sidebar.php";
?>

<main class="main-content">

<style>
.change-password-page {
    padding: 35px;
}

.password-heading {
    margin-bottom: 25px;
}

.password-heading h1 {
    font-family: 'Playfair Display', serif;
    color: #294D3E;
    font-size: 29px;
    font-weight: 500;
}

.password-heading p {
    color: #85877E;
    font-size: 13px;
    margin-top: 7px;
}

.password-card {
    background: #FFFFFF;
    border: 1px solid #E5E5DE;
    border-radius: 8px;
    padding: 30px;
    max-width: 600px;
}

.password-card h3 {
    color: #294D3E;
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    font-weight: 500;
    margin-bottom: 8px;
}

.password-description {
    color: #85877E;
    font-size: 12px;
    margin-bottom: 25px;
}

.password-group {
    margin-bottom: 20px;
}

.password-group label {
    display: block;
    font-size: 12px;
    color: #535A53;
    margin-bottom: 8px;
}

.password-group input {
    width: 100%;
    padding: 12px;
    border: 1px solid #DDDED5;
    border-radius: 5px;
    font-size: 13px;
    box-sizing: border-box;
    outline: none;
}

.password-group input:focus {
    border-color: #294D3E;
}

.password-submit {
    background: #294D3E;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 13px;
}

.password-submit:hover {
    background: #1F3D30;
}

.password-message {
    padding: 13px 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    font-size: 13px;
}

.password-message.success {
    background: #E7F1E9;
    color: #294D3E;
}

.password-message.error {
    background: #F9E8E6;
    color: #A33E35;
}

.password-tip {
    background: #F7F6F2;
    padding: 15px;
    margin-top: 25px;
    border-radius: 5px;
    color: #666D64;
    font-size: 12px;
    line-height: 1.7;
}

@media(max-width: 650px) {
    .change-password-page {
        padding: 20px 12px;
    }

    .password-card {
        padding: 20px;
    }
}
</style>

<div class="change-password-page">

    <div class="password-heading">
        <h1>Change Password</h1>
        <p>Keep your NESTORA administrator account secure.</p>
    </div>

    <div class="password-card">

        <h3>Update your password</h3>

        <p class="password-description">
            Enter your current password and choose a new password.
        </p>

        <?php if ($message): ?>
            <div class="password-message <?= htmlspecialchars($messageType) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>"
            >

            <div class="password-group">
                <label>Current Password</label>
                <input
                    type="password"
                    name="current_password"
                    placeholder="Enter your current password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <div class="password-group">
                <label>New Password</label>
                <input
                    type="password"
                    name="new_password"
                    placeholder="Enter your new password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>

            <div class="password-group">
                <label>Confirm New Password</label>
                <input
                    type="password"
                    name="confirm_password"
                    placeholder="Confirm your new password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>

            <button type="submit" class="password-submit">
                Change Password
            </button>

        </form>

        <div class="password-tip">
            <strong>Password security tip</strong><br>
            Use at least 8 characters and combine uppercase letters,
            lowercase letters, numbers and special characters.
        </div>

    </div>

</div>

</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
