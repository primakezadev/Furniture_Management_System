
<?php
session_start();
require_once __DIR__ . '/config/database.php';

$error = "";
$first_name = "";
$last_name = "";
$email = "";
$phone = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first_name = trim($_POST["first_name"] ?? "");
    $last_name = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    $terms = isset($_POST["terms"]);

    if (
        empty($first_name) || empty($last_name) ||
        empty($email) || empty($phone) ||
        empty($password) || empty($confirm_password)
    ) {
        $error = "Please fill in all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 8) {
        $error = "Password must contain at least 8 characters.";

    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match. Please try again.";

    } elseif (!$terms) {
        $error = "Please agree to the Terms of Service and Privacy Policy.";

    } else {
        try {
            // Check whether the email already exists
            $check = $conn->prepare(
                "SELECT customer_id FROM customers WHERE email = :email"
            );

            $check->execute([':email' => $email]);

            if ($check->fetch()) {
                $error = "This email address is already registered.";

            } else {
                $full_name = $first_name . " " . $last_name;
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Save customer details
                $sql = "INSERT INTO customers
                        (full_name, email, phone, password)
                        VALUES (:full_name, :email, :phone, :password)";

                $stmt = $conn->prepare($sql);

                $stmt->execute([
                    ':full_name' => $full_name,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':password' => $hashed_password
                ]);

                $_SESSION['registration_success'] =
                    "Account created successfully! Please sign in.";

                header("Location: admin/login.php");
                exit;
            }

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Form & Field</title>
    <link rel="stylesheet" href="assets/css/register.css">
</head>

<body>

<div class="register-container">

    <!-- LEFT IMAGE SECTION -->
    <div class="image-section">

        <div class="brand">
            <h2>FORM & FIELD</h2>
            <p>FURNITURE FOR A CONSIDERED LIFE</p>
        </div>

        <div class="image-text">
            <span>THOUGHTFUL BY NATURE</span>

            <h1>Good things start<br>with a little space.</h1>

            <p>
                Honest materials. Enduring forms. Furniture that
                makes room for the life you love.
            </p>

            <a href="customer/dashboard.php">Discover the collection ↗</a>
        </div>

    </div>

    <!-- RIGHT FORM SECTION -->
    <div class="form-section">

        <div class="top-link">
            Already part of the family?
            <a href="admin/login.php">Sign in</a>
        </div>

        <div class="form-content">

            <span class="small-heading">YOUR HOME, YOUR WAY</span>

            <h1>Join the considered life.</h1>

            <p class="description">
                Create your customer account to save time at checkout
                and keep track of every order.
            </p>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="name-fields">

                    <div class="form-group">
                        <label>First name *</label>
                        <input type="text"
                               name="first_name"
                               placeholder="First name"
                               value="<?= htmlspecialchars($first_name) ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label>Last name *</label>
                        <input type="text"
                               name="last_name"
                               placeholder="Last name"
                               value="<?= htmlspecialchars($last_name) ?>"
                               required>
                    </div>

                </div>

                <div class="form-group">
                    <label>Email address *</label>
                    <input type="email"
                           name="email"
                           placeholder="yourname@email.com"
                           value="<?= htmlspecialchars($email) ?>"
                           required>
                </div>

                <div class="form-group">
                    <label>Phone number *</label>
                    <input type="tel"
                           name="phone"
                           placeholder="+250 7XX XXX XXX"
                           value="<?= htmlspecialchars($phone) ?>"
                           required>
                </div>

                <div class="form-group">
                    <label>Password *</label>

                    <div class="password-field">
                        <input type="password"
                               name="password"
                               id="password"
                               placeholder="Create a password"
                               minlength="8"
                               required>

                        <button type="button"
                                onclick="togglePassword('password', this)">
                            ◉
                        </button>
                    </div>

                    <small>
                        Strong password · At least 8 characters,
                        a number and a symbol.
                    </small>
                </div>

                <div class="form-group">
                    <label>Confirm password *</label>

                    <div class="password-field">
                        <input type="password"
                               name="confirm_password"
                               id="confirm_password"
                               placeholder="Confirm your password"
                               minlength="8"
                               required>

                        <button type="button"
                                onclick="togglePassword('confirm_password', this)">
                            ◉
                        </button>
                    </div>

                    <small id="password-message"></small>
                </div>

                <div class="terms">
                    <input type="checkbox" name="terms" id="terms" required>

                    <label for="terms">
                        I agree to the Terms of Service and Privacy Policy.
                    </label>
                </div>

                <button type="submit" class="register-button">
                    Create customer account <span>→</span>
                </button>

            </form>

        </div>

        <div class="footer">
            © 2026 Form & Field
            <span>·</span> Privacy policy
            <span>·</span> Need help?
        </div>

    </div>

</div>

<script>
function togglePassword(id, button) {
    const field = document.getElementById(id);

    if (field.type === "password") {
        field.type = "text";
        button.textContent = "◎";
    } else {
        field.type = "password";
        button.textContent = "◉";
    }
}

// Check password confirmation
const password = document.getElementById("password");
const confirmPassword = document.getElementById("confirm_password");
const message = document.getElementById("password-message");

function checkPasswords() {
    if (confirmPassword.value === "") {
        message.textContent = "";
        message.className = "";
    } else if (password.value !== confirmPassword.value) {
        message.textContent = "Passwords don't match. Re-enter your password.";
        message.className = "password-error";
    } else {
        message.textContent = "Passwords match.";
        message.className = "password-success";
    }
}

password.addEventListener("input", checkPasswords);
confirmPassword.addEventListener("input", checkPasswords);
</script>

</body>
</html>
