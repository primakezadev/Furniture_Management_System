<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: forgot_password.php");
    exit;
}

if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    die("Invalid request. Please refresh the Forgot Password page and try again.");
}



$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Please enter a valid email address.");
}

// 2. Check whether customer exists
$stmt = $conn->prepare(
    "SELECT customer_id FROM customers WHERE email = ? LIMIT 1"
);

$stmt->execute([$email]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

// Always show the same response whether the email exists or not.
$message = "If an account exists with that email address, a password reset link has been sent.";

if ($customer) {

    try {

        // 3. Generate secure token
        $token = bin2hex(random_bytes(32));

        // Store only the token hash
        $tokenHash = hash('sha256', $token);

        // Token expires after 30 minutes

        // 4. Remove previous customer reset requests
        $delete = $conn->prepare(
            "DELETE FROM password_resets
             WHERE email = ? AND role = 'customer'"
        );

        $delete->execute([$email]);

        // 5. Store new reset request
     $insert = $conn->prepare(
    "INSERT INTO password_resets
     (email, role, token_hash, expires_at)
     VALUES (?, 'customer', ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))"
);

$insert->execute([
    $email,
    $tokenHash
]);

        // 6. Generate reset link
        $resetLink = "http://localhost/Furniture_Management_System/authentication/reset_password.php"
            . "?token=" . urlencode($token)
            . "&email=" . urlencode($email);

        // 7. Send email using PHPMailer
        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        // CHANGE THESE TWO VALUES
        $mail->Username = 'kezaprim@gmail.com';
        $mail->Password = 'qjkr elyd flck cxcn';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom(
            'kezaprim@gmail.com',
            'Form & Field'
        );

        $mail->addAddress($email);

        $mail->isHTML(true);

        $mail->Subject = 'Reset Your NESTORA Password';

        $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

        $mail->Body = "
            <div style='font-family:Arial,sans-serif;
                        max-width:550px;
                        margin:auto;
                        padding:30px;
                        background:#f7f5f0;
                        color:#303832;'>

                <h2 style='color:#294e40;'>
                    NESTORA
                </h2>

                <h3>Reset your password</h3>

                <p>
                    We received a request to reset your
                    NESTORA customer account password.
                </p>

                <p>
                    Click the button below to create
                    a new password.
                </p>

                <a href='$safeLink'
                   style='display:inline-block;
                          background:#294e40;
                          color:white;
                          padding:14px 25px;
                          text-decoration:none;
                          border-radius:4px;'>
                    Reset Password
                </a>

                <p style='margin-top:25px;font-size:13px;'>
                    This link expires in 30 minutes.
                    If you did not request this change,
                    you can ignore this email.
                </p>

                <p style='font-size:12px;color:#777;'>
                    Form & Field — Furniture for a considered life.
                </p>

            </div>
        ";

        $mail->AltBody = "Reset your password using this link: $resetLink";

        $mail->send();

    } catch (Exception $e) {

        // Do not expose SMTP errors to customers.
        error_log("Password reset email error: " . $e->getMessage());

        if (isset($tokenHash)) {
            $deleteFailed = $conn->prepare(
                "DELETE FROM password_resets
                 WHERE email = ? AND role = 'customer'
                 AND token_hash = ?"
            );

            $deleteFailed->execute([$email, $tokenHash]);
        }

        $message = "We could not send your reset email. Please try again later.";
    }
}

// 8. Display response
unset($_SESSION['csrf_token']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Recovery | NESTORA</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>

<body>

<div class="auth-wrapper">

    <section class="auth-image">

        <div class="brand">
            FORM & FIELD
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

    <section class="auth-content">

        <div class="auth-top">
            <a href="../login.php">← Back to your account</a>
        </div>

        <div class="auth-form">

            <div class="auth-icon">✓</div>

            <div class="eyebrow">ACCOUNT SECURITY</div>

            <h2>Check your inbox.</h2>

            <p class="auth-description">
                <?= htmlspecialchars($message) ?>
            </p>

            <a href="forgot_password.php" class="auth-button"
               style="display:block;text-align:center;text-decoration:none;">
                Try again
            </a>

            <a href="../login.php" class="auth-back">
                ← Return to login
            </a>

        </div>

        <div class="auth-footer">
            <span>© 2026 Form & Field</span>
            <span>·</span>
            <a href="#">Privacy policy</a>
            <span>·</span>
            <a href="#">Need help?</a>
        </div>

    </section>

</div>

</body>
</html>
