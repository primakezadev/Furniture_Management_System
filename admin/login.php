<?php
session_start();

require_once __DIR__ . '/../config/database.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "customer";
  



    if (empty($email) || empty($password)) {
        $error = "Please enter your email and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {

        try {

            if ($role === "admin") {

                $sql = "SELECT admin_id, username, email, password
                        FROM admins
                        WHERE email = :email";

                $stmt = $conn->prepare($sql);
                $stmt->execute([':email' => $email]);

                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user["password"])) {

                    session_regenerate_id(true);

                    $_SESSION["admin_id"] = $user["admin_id"];
                    $_SESSION["admin_username"] = $user["username"];

                    header("Location: dashboard.php");
                    exit;

                } else {
                    $error = "The email and password don't match. Please try again.";
                }

            } else {

                $sql = "SELECT customer_id, full_name, email, password
                        FROM customers
                        WHERE email = :email";

                $stmt = $conn->prepare($sql);
                $stmt->execute([':email' => $email]);

                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user["password"])) {

                    session_regenerate_id(true);

                    $_SESSION["customer_id"] = $user["customer_id"];
                    $_SESSION["customer_name"] = $user["full_name"];

                    header("Location: ../customer/dashboard.php");
                    exit;

                } else {
                    $error = "The email and password don't match. Please try again.";
                }
            }

        } catch (PDOException $e) {

            error_log("Login error: " . $e->getMessage());
            $error = "A system error occurred. Please try again.";

        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sign In | Form & Field</title>

    <link rel="stylesheet" href="../assets/css/login.css">
</head>

<body>

<div class="login-page">

    <!-- LEFT BRANDING SECTION -->

    <section class="brand-section">

        <div class="brand-logo">
            <h1>FORM & FIELD</h1>
            <p>FURNITURE FOR A CONSIDERED LIFE</p>
        </div>

        <div class="brand-content">

            <span>THOUGHTFULLY BY NATURE</span>

            <h2>
                A little more natural.<br>
                A little more you.
            </h2>

            <p>
                Honest materials. Enduring forms.
                Furniture that makes room for the life you love.
            </p>

            <a href="../index.php">Discover the collection ↗</a>

        </div>

    </section>


    <!-- RIGHT LOGIN SECTION -->

    <section class="form-section">

        <div class="top-link">
            New here?
            <a href="../register.php">Create an account</a>
        </div>

        <div class="login-content">

            <div class="welcome-text">

                <span>WELCOME TO FORM & FIELD</span>

                <h2>Make yourself at home.</h2>

                <p>Sign in to discover considered pieces for your space.</p>

            </div>


            <form method="POST" action="">

                <label class="role-label">I'm signing in as</label>

                <div class="role-selection">

    <label class="role-option selected">
        <input
            type="radio"
            name="role"
            value="customer"
            checked
        >

        <span>♙</span>
        Customer
        <span class="check">✓</span>
    </label>

    <label class="role-option">
        <input
            type="radio"
            name="role"
            value="admin"
        >

        <span>♢</span>
        Administrator
        <span class="check">✓</span>
    </label>

</div>


                <?php if (!empty($error)): ?>

                    <div class="error-message">
                        <span>ⓘ</span>

                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>

                <?php endif; ?>


                <div class="form-group">

                    <label for="email">Email address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email address"
                        value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, 'UTF-8') ?>"
                        required
                        autocomplete="email"
                    >

                </div>


                <div class="form-group">

                    <label for="password">Password</label>

                    <div class="password-wrapper">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword()"
                            aria-label="Show or hide password"
                        >
                            ◉
                        </button>

                    </div>

                </div>


                <div class="form-options">

                    <label class="remember">
                        <input type="checkbox" name="remember">
                        Remember me
                    </label>

                    <a href="#">Need help signing in?</a>

                </div>


                <button type="submit" class="sign-in-button">
                    Sign in <span>→</span>
                </button>


                <p class="register-link">
                    Not a customer yet?
                    <a href="../register.php">Create an account</a>
                </p>

            </form>

        </div>


        <footer class="login-footer">
            © 2026 Form & Field
            <span>·</span>
            <a href="#">Privacy policy</a>
            <span>·</span>
            <a href="#">Need help?</a>
        </footer>

    </section>

</div>


<script>

function togglePassword() {

    const password = document.getElementById("password");

    if (password.type === "password") {
        password.type = "text";
    } else {
        password.type = "password";
    }

}




document.querySelectorAll('input[name="role"]').forEach(function(input) {

    input.addEventListener("change", function() {

        document.querySelectorAll(".role-option").forEach(function(option) {
            option.classList.remove("selected");
        });

        this.closest(".role-option").classList.add("selected");

    });

});

</script>

</body>
</html>