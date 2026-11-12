
<?php
session_start();

// Redirect logged-in users to their dashboards
if (isset($_SESSION['admin_id'])) {
    header("Location: admin/dashboard.php");
    exit;
}

if (isset($_SESSION['customer_id'])) {
    header("Location: customer/dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Welcome to NESTORA</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            height: 100vh;
            font-family: Arial, sans-serif;
            background:
                linear-gradient(
                    rgba(0, 0, 0, 0.55),
                    rgba(0, 0, 0, 0.65)
                ),
                url("assets/images/background.jpeg");

            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;

            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            overflow: hidden;
        }

        .splash-container {
            text-align: center;
            padding: 30px;
            animation: fadeIn 2s ease-in-out;
        }

        .logo {
            font-family: Georgia, serif;
            font-size: 65px;
            font-weight: normal;
            letter-spacing: 8px;
            margin-bottom: 15px;
        }

        .tagline {
            font-size: 12px;
            letter-spacing: 5px;
            margin-bottom: 45px;
        }

        .welcome-text {
            font-family: Georgia, serif;
            font-size: 38px;
            font-weight: normal;
            margin-bottom: 18px;
        }

        .description {
            font-size: 15px;
            line-height: 1.8;
            color: #eeeeee;
            max-width: 500px;
            margin: 0 auto 35px;
        }

        .loader {
            width: 35px;
            height: 35px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            margin: 0 auto 20px;
            animation: spin 1s linear infinite;
        }

        .loading-text {
            font-size: 11px;
            letter-spacing: 4px;
        }

        .welcome-home {
            margin-top: 50px;
            font-size: 10px;
            letter-spacing: 5px;
            color: #dddddd;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 600px) {
            .logo {
                font-size: 42px;
                letter-spacing: 5px;
            }

            .tagline {
                font-size: 9px;
                letter-spacing: 3px;
            }

            .welcome-text {
                font-size: 28px;
            }

            .description {
                font-size: 13px;
            }
        }
    </style>
</head>

<body>

    <div class="splash-container">

        <h1 class="logo">NESTORA</h1>

        <p class="tagline">
            FURNITURE FOR A CONSIDERED LIFE
        </p>

        <h2 class="welcome-text">
            A little more natural.<br>
            A little more you.
        </h2>

        <p class="description">
            Discover thoughtful furniture and timeless pieces
            designed to bring comfort, beauty and meaning
            into your everyday living.
        </p>

        <div class="loader"></div>

        <p class="loading-text">WELCOME HOME</p>

        <p class="welcome-home">THOUGHTFUL BY NATURE</p>

    </div>

    <script>
        // Continue to the public furniture collection after 5 seconds
        setTimeout(function() {
            window.location.href = "customer/dashboard.php";
        }, 5000);
    </script>

</body>
</html>
