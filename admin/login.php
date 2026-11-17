
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        $error = "Please enter your email and password.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    } else {

        try {

            // Search only in the administrators table
            $sql = "SELECT admin_id, username, email, password
                    FROM admins
                    WHERE email = :email";

            $stmt = $conn->prepare($sql);
            $stmt->execute([':email' => $email]);

            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin["password"])) {

                session_regenerate_id(true);

                $_SESSION["admin_id"] = $admin["admin_id"];
                $_SESSION["admin_username"] = $admin["username"];

                header("Location: dashboard.php");
                exit;

            } else {
                $error = "Invalid administrator email or password.";
            }

        } catch (PDOException $e) {

            error_log("Admin login error: " . $e->getMessage());
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

    <title>Administrator Login | NESTORA</title>

    <link rel="stylesheet" href="../assets/css/login.css">
</head>

<body>

<div class="login-page">

    <section class="brand-section">

        <div class="brand-logo">
            <h1>NESTORA</h1>
            <p>FURNITURE FOR A CONSIDERED LIFE</p>
        </div>

        <div class="brand-content">

            <span>ADMINISTRATION PORTAL</span>

            <h2>
                Manage your space.<br>
                Manage your store.
            </h2>

            <p>
                Access the NESTORA administration portal
                to manage furniture, customers and orders.
            </p>

            <a href="../index.php">Return to the website ↗</a>

        </div>

    </section>


    <section class="form-section">

        <div class="top-link">
            <a href="../login.php">Customer login</a>
        </div>

        <div class="login-content">

            <div class="welcome-text">

                <span>WELCOME TO NESTORA ADMINISTRATION</span>

                <h2>Administrator sign in.</h2>

                <p>
                    Sign in with your administrator account
                    to access the management dashboard.
                </p>

            </div>


            <form method="POST" action="">

                <?php if (!empty($error)): ?>

                    <div class="error-message">
                        <span>ⓘ</span>

                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>

                <?php endif; ?>


                <div class="form-group">

                    <label for="email">Administrator email</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your administrator email"
                        value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, 'UTF-8') ?>"
                        required
                        autocomplete="username"
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

                    <a href="../authentication/forgot_password.php">
                        Forgot password?
                    </a>

                </div>


                <button type="submit" class="sign-in-button">
                    Administrator Sign In <span>→</span>
                </button>

            </form>

        </div>


        <footer class="login-footer">
            © 2026 NESTORA
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

    password.type =
        password.type === "password" ? "text" : "password";

}

</script>

</body>
</html>
