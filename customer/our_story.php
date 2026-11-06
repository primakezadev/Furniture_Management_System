
<?php
session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../admin/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Our Story | NESTORA</title>

    <link rel="stylesheet" href="../assets/css/customer.css">

    <style>
        .story-page {
            background: #f7f5f0;
            min-height: 100vh;
            color: #303832;
        }

        .story-hero {
            min-height: 520px;
            background:
                linear-gradient(rgba(0,0,0,.35), rgba(0,0,0,.45)),
                url('../assets/images/background.jpeg') center/cover;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding: 40px 20px;
        }

        .story-hero h1 {
            font-size: clamp(38px, 6vw, 65px);
            font-weight: normal;
            margin: 20px 0;
        }

        .story-hero p {
            max-width: 550px;
            line-height: 1.8;
            margin: auto;
        }

        .story-content {
            max-width: 850px;
            margin: auto;
            padding: 90px 25px;
            text-align: center;
        }

        .story-content h2 {
            font-size: 35px;
            font-weight: normal;
            margin-bottom: 25px;
        }

        .story-content p {
            line-height: 2;
            color: #73776f;
            margin-bottom: 20px;
        }

        .story-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 35px;
            flex-wrap: wrap;
        }

        .story-links a {
            padding: 14px 25px;
            background: #294e40;
            color: white;
            text-decoration: none;
        }

        .story-footer {
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e3e0d8;
            font-size: 12px;
        }
    </style>
</head>

<body class="story-page">

<header class="navbar">

    <a href="dashboard.php" class="logo">
        NESTORA
        <small>FURNITURE FOR A CONSIDERED LIFE</small>
    </a>

    <nav>
        <a href="dashboard.php">Furniture</a>
        <a href="living.php">Living</a>
        <a href="dining.php">Dining</a>
        <a href="our-story.php" class="active">Our Story</a>
        <a href="order_history.php">My Orders</a>
    </nav>

</header>

<section class="story-hero">

    <div>
        <span>OUR PHILOSOPHY</span>

        <h1>A home should tell your story.</h1>

        <p>
            We believe furniture is more than something
            that fills a room. It creates spaces for living,
            connecting and making memories.
        </p>
    </div>

</section>

<section class="story-content">

    <span>THE NESTORA STORY</span>

    <h2>Thoughtful design. Meaningful living.</h2>

    <p>
        NESTORA is built around the belief that a beautiful
        home begins with thoughtful choices. Every piece
        should bring comfort, character and purpose to
        the spaces where life happens.
    </p>

    <p>
        From welcoming living rooms to memorable dining
        spaces, our collections are designed to make
        everyday moments feel special.
    </p>

    <div class="story-links">

        <a href="living.php">Explore Living</a>

        <a href="dining.php">Explore Dining</a>

    </div>

</section>

<footer class="story-footer">
    © 2026 NESTORA · Furniture for a considered life.
</footer>

</body>
</html>
