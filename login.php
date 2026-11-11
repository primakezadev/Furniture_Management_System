
<?php
session_start();

require_once __DIR__ . "/authentication/login_process.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login | NESTORA</title>
    <link rel="stylesheet" href="assets/css/register.css">
</head>

<body>

<div class="register-container">

    <div class="image-section">
        <div class="brand">
            <h2>NESTORA</h2>
            <p>FURNITURE FOR A CONSIDERED LIFE</p>
        </div>

        <div class="image-text">
            <span>THOUGHTFUL BY NATURE</span>

            <h1>Welcome back<br>to your space.</h1>

            <p>
                Discover timeless furniture and thoughtful pieces
                designed to make your house feel like home.
            </p>

            <a href="../customer/dashboard.php">Discover the collection ↗</a>
        </div>
    </div>

    <div class="form-section">

        <div class="top-link">
            New to NESTORA?
            <a href="register.php">Create an account</a>
        </div>

        <div class="form-content">

            <span class="small-heading">YOUR HOME, YOUR WAY</span>

            <h1>Welcome back.</h1>

            <p class="description">
                Sign in to continue exploring your favourite furniture
                and manage your orders.
            </p>

            <?php if (isset($_SESSION["registration_success"])): ?>
                <div class="success-message">
                    <?= htmlspecialchars($_SESSION["registration_success"]) ?>
                </div>
                <?php unset($_SESSION["registration_success"]); ?>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <input type="hidden" name="role" value="customer">

                <div class="form-group">
                    <label>Email address *</label>
                    <input
                        type="email"
                        name="email"
                        placeholder="yourname@email.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Password *</label>

                    <div class="password-field">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="Enter your password"
                            required
                        >

                        <button
                            type="button"
                            onclick="togglePassword()"
                        >◉</button>
                    </div>
                </div>

                <button type="submit" class="register-button">
                    Sign in <span>→</span>
                </button>

            </form>

            <p class="description" style="margin-top:20px;">
                Forgot your password?
                <a href="authentication/forgot_password.php">
                    Reset it here
                </a>
            </p>

        </div>

        <div class="footer">
            © 2026 NESTORA
            <span>·</span> Privacy policy
            <span>·</span> Need help?
        </div>

    </div>

</div>

<script>
function togglePassword() {
    const field = document.getElementById("password");

    field.type = field.type === "password"
        ? "text"
        : "password";
}
</script>

</body>
</html>
