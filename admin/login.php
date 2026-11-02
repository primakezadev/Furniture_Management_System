
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        try {
            $sql = "SELECT admin_id, username, password
                    FROM admins
                    WHERE username = :username";

            $stmt = $conn->prepare($sql);
            $stmt->execute([':username' => $username]);

            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin["password"])) {

                session_regenerate_id(true);

                $_SESSION["admin_id"] = $admin["admin_id"];
                $_SESSION["admin_username"] = $admin["username"];

                header("Location: dashboard.php");
                exit;

            } else {
                $error = "Invalid username or password.";
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
    <title>Admin Login | Furniture Management</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, sans-serif;
            background: #f3f0eb;
            color: #29251f;
        }

        .login-container {
            width: 90%;
            max-width: 400px;
            padding: 35px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        h2 {
            text-align: center;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .subtitle {
            text-align: center;
            color: #777;
            margin-bottom: 25px;
            font-size: 14px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 18px;
            border: 1px solid #d8d3cc;
            border-radius: 5px;
            font-size: 15px;
        }

        input:focus {
            outline: 2px solid #a78b65;
            border-color: #a78b65;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #65513b;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #4d3d2c;
        }

        .error {
            padding: 10px;
            margin-bottom: 18px;
            background: #fde8e8;
            color: #a12626;
            border-radius: 5px;
            font-size: 14px;
        }
    </style>
</head>

<body>

<div class="login-container">

    <h2>Admin Login</h2>

    <p class="subtitle">Furniture Management System</p>

    <?php if (!empty($error)): ?>
        <div class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">

        <label for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            required
            autocomplete="username"
        >

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            required
            autocomplete="current-password"
        >

        <button type="submit">Login</button>

    </form>

</div>

</body>
</html>