
<?php

require_once __DIR__ . "/../config/database.php";

$error = "";

$first_name = "";
$last_name = "";
$email = "";
$phone = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Collect form information
    $first_name = trim($_POST["first_name"] ?? "");
    $last_name = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    $terms = isset($_POST["terms"]);

    // Validate required fields
    if (
        empty($first_name) ||
        empty($last_name) ||
        empty($email) ||
        empty($phone) ||
        empty($password) ||
        empty($confirm_password)
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

            // Check whether email already exists
            $check = $conn->prepare("
                SELECT customer_id
                FROM customers
                WHERE email = :email
            ");

            $check->execute([
                ":email" => $email
            ]);

            if ($check->fetch()) {

                $error = "This email address is already registered.";

            } else {

                // Combine first name and last name
                $full_name = $first_name . " " . $last_name;

                // Hash password before saving
                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Insert customer information
                $sql = "
                    INSERT INTO customers
                    (full_name, email, phone, password)
                    VALUES
                    (:full_name, :email, :phone, :password)
                ";

                $stmt = $conn->prepare($sql);

                $stmt->execute([
                    ":full_name" => $full_name,
                    ":email" => $email,
                    ":phone" => $phone,
                    ":password" => $hashed_password
                ]);

                // Success message
                $_SESSION["registration_success"] =
                    "Account created successfully! Please sign in.";

                header("Location: admin/login.php");
                exit;
            }

        } catch (PDOException $e) {

            error_log("Registration error: " . $e->getMessage());

            $error = "Something went wrong. Please try again.";
        }
    }
}
?>
