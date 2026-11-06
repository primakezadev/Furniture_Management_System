<?php
session_start();

// Generate CSRF token if it does not exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Forgot Password | NESTORA</title>

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


    <!-- RIGHT FORM SECTION -->
    <section class="auth-content">

        <div class="auth-top">

            <a href="../admin/login.php">
                ← Back to your account
            </a>

            <span>·</span>

            <a href="..admin/login.php">
                Log in
            </a>

        </div>


        <div class="auth-form">

            <div class="auth-icon">
                ♧
            </div>

            <div class="eyebrow">
                ACCOUNT SECURITY
            </div>

            <h2>Forgot your password?</h2>

            <p class="auth-description">
                No worries. Enter the email address associated
                with your account and we'll send you a secure
                link to reset your password.
            </p>


           <form action="send_reset_email.php" method="POST">

    <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
    >

    <div class="form-group">
        <label for="email">Email address</label>

        <input
            type="email"
            id="email"
            name="email"
            placeholder="Enter your registered email"
            autocomplete="email"
            required
        >
    </div>

    <button type="submit" class="auth-button">
        Send reset link
    </button>

</form>


            <a href="../login.php" class="auth-back">
                Return to login
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
