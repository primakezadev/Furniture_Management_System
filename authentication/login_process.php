
<?php

require_once __DIR__ . "/../config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "customer";

    // Validate input
    if (empty($email) || empty($password)) {

        $error = "Please enter your email and password.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (!in_array($role, ["admin", "customer"], true)) {

        $error = "Invalid account type.";

    } else {

        try {

            if ($role === "admin") {

                $sql = "SELECT admin_id, username, email, password
                        FROM admins
                        WHERE email = :email";

                $stmt = $conn->prepare($sql);
                $stmt->execute([":email" => $email]);

                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user["password"])) {

                    session_regenerate_id(true);

                    $_SESSION["admin_id"] = $user["admin_id"];
                    $_SESSION["admin_username"] = $user["username"];

                    header("Location: ../admin/dashboard.php");
                    exit;

                } else {

                    $error = "The email and password don't match. Please try again.";
                }

            } else {

                $sql = "SELECT customer_id, full_name, email, password
                        FROM customers
                        WHERE email = :email";

                $stmt = $conn->prepare($sql);
                $stmt->execute([":email" => $email]);

                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user["password"])) {

                    session_regenerate_id(true);

                    $_SESSION["customer_id"] = $user["customer_id"];
                    $_SESSION["customer_name"] = $user["full_name"];

                 header("Location: customer/dashboard.php");
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
