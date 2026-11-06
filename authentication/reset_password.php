
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

$error = "";
$success = false;

// Generate CSRF token
if (empty($_SESSION['reset_csrf_token'])) {
    $_SESSION['reset_csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['reset_csrf_token'];

// Get token and email from URL or submitted form
$token = $_POST['token'] ?? $_GET['token'] ?? '';
$email = $_POST['email'] ?? $_GET['email'] ?? '';

$token = trim($token);
$email = trim($email);

$resetRequest = null;

// Validate email and token
if (
    empty($token) ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    $error = "Invalid or incomplete reset link. Please request a new password reset.";
} else {

    // Hash the token before comparing with database
    $tokenHash = hash('sha256', $token);

    $stmt = $conn->prepare(
        "SELECT reset_id
         FROM password_resets
         WHERE email = ?
         AND role = 'customer'
         AND token_hash = ?
         AND expires_at > NOW()
         LIMIT 1"
    );

    $stmt->execute([$email, $tokenHash]);

    $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resetRequest) {
        $error = "This password reset link is invalid or has expired. Please request a new one.";
    }
}


// Process new password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $resetRequest) {

    // Verify CSRF token
    $submittedCsrf = $_POST['csrf_token'] ?? '';

    if (
        empty($submittedCsrf) ||
        !hash_equals($_SESSION['reset_csrf_token'], $submittedCsrf)
    ) {
        $error = "Invalid request. Please refresh the page and try again.";
    } else {

        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($newPassword) || empty($confirmPassword)) {

            $error = "Please fill in all password fields.";

        } elseif (strlen($newPassword) < 8) {

            $error = "Your password must contain at least 8 characters.";

        } elseif (!preg_match('/[0-9]/', $newPassword)) {

            $error = "Your password must contain at least one number.";

        } elseif (!preg_match('/[^a-zA-Z0-9]/', $newPassword)) {

            $error = "Your password must contain at least one symbol.";

        } elseif ($newPassword !== $confirmPassword) {

            $error = "The passwords do not match.";

        } else {

            try {

                // Hash the new password
                $hashedPassword = password_hash(
                    $newPassword,
                    PASSWORD_DEFAULT
                );

                $conn->beginTransaction();

                // Update customer password
                $update = $conn->prepare(
                    "UPDATE customers
                     SET password = ?
                     WHERE email = ?"
                );

                $update->execute([
                    $hashedPassword,
                    $email
                ]);

                if ($update->rowCount() !== 1) {
                    throw new Exception("Customer password could not be updated.");
                }

                // Delete the used reset token
                $delete = $conn->prepare(
                    "DELETE FROM password_resets
                     WHERE reset_id = ?"
                );

                $delete->execute([
                    $resetRequest['reset_id']
                ]);

                $conn->commit();

                $success = true;

                // Remove reset CSRF token
                unset($_SESSION['reset_csrf_token']);

            } catch (Exception $e) {

                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }

                error_log("Password reset error: " . $e->getMessage());

                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reset Password | NESTORA</title>

    <link rel="stylesheet" href="../assets/css/auth.css">
</head>

<body>

<div class="auth-wrapper">

    <!-- LEFT IMAGE SECTION -->
    <section class="auth-image">

        <div class="brand">
            NESTORA
            <small>FURNITURE FOR A CONSIDERED LIFE</small>
        </div>

        <div class="image-caption">

            <small>THOUGHTFUL BY NATURE</small>

            <h1>
                A considered home.<br>
                A protected account.
            </h1>

            <p>
                Honest materials. Enduring forms.
                Furniture that makes room for the life you love.
            </p>

        </div>

    </section>


    <!-- RIGHT CONTENT SECTION -->
    <section class="auth-content">

        <div class="auth-top">

            <a href="../login.php">
                ← Back to your account
            </a>

        </div>


        <div class="auth-form">

            <div class="auth-icon">
                ♧
            </div>

            <div class="eyebrow">
                ACCOUNT SECURITY
            </div>


            <?php if ($success): ?>

                <h2>Password updated!</h2>

                <p class="auth-description">
                    Your password has been changed successfully.
                    You can now log in using your new password.
                </p>

                <a href="../login.php" class="auth-button"
                   style="display:block;text-align:center;text-decoration:none;">
                    Continue to login
                </a>

            <?php else: ?>

                <h2>Create a new password.</h2>

                <p class="auth-description">
                    Choose a strong password to keep your
                    NESTORA account secure.
                </p>


                <?php if (!empty($error)): ?>

                    <div class="auth-error">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>

                <?php endif; ?>


                <?php if ($resetRequest): ?>

                    <form method="POST" action="reset_password.php">

                        <input type="hidden"
                               name="token"
                               value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                        <input type="hidden"
                               name="email"
                               value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">


                        <div class="form-group">

                            <label for="new_password">
                                New password
                            </label>

                            <input
                                type="password"
                                id="new_password"
                                name="new_password"
                                placeholder="Enter new password"
                                autocomplete="new-password"
                                required
                            >

                            <small>
                                Use at least 8 characters, including a number and a symbol.
                            </small>

                        </div>


                        <div class="form-group">

                            <label for="confirm_password">
                                Confirm new password
                            </label>

                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                placeholder="Confirm new password"
                                autocomplete="new-password"
                                required
                            >

                        </div>


                        <button type="submit" class="auth-button">
                            Reset password
                        </button>

                    </form>

                <?php else: ?>

                    <a href="forgot_password.php" class="auth-button"
                       style="display:block;text-align:center;text-decoration:none;">
                        Request a new reset link
                    </a>

                <?php endif; ?>

            <?php endif; ?>


            <a href="../admin/login.php" class="auth-back">
                ← Return to login
            </a>

        </div>


        <div class="auth-footer">

            <span>© 2026 NESTORA</span>
            <span>·</span>
            <a href="#">Privacy policy</a>
            <span>·</span>
            <a href="#">Need help?</a>

        </div>

    </section>

</div>

</body>
</html>
