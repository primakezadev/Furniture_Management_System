
<?php
session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../admin/login.php");
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF']);
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
                linear-gradient(rgba(0,0,0,.38), rgba(0,0,0,.48)),
                url('../assets/images/background.jpeg') center/cover;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding: 40px 20px;
        }

        .story-hero-content {
            max-width: 750px;
        }

        .story-hero span,
        .story-content > span,
        .values-section > span {
            letter-spacing: 3px;
            font-size: 12px;
            font-weight: 600;
        }

        .story-hero h1 {
            font-size: clamp(38px, 6vw, 65px);
            font-weight: normal;
            line-height: 1.15;
            margin: 25px 0;
        }

        .story-hero p {
            max-width: 550px;
            line-height: 1.9;
            margin: auto;
            font-size: 16px;
        }

        .story-content {
            max-width: 850px;
            margin: auto;
            padding: 90px 25px;
            text-align: center;
        }

        .story-content > span,
        .values-section > span {
            color: #a07852;
        }

        .story-content h2 {
            font-size: clamp(28px, 4vw, 38px);
            font-weight: normal;
            margin: 22px 0;
        }

        .story-content p {
            line-height: 2;
            color: #73776f;
            margin-bottom: 20px;
        }

        .story-links {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 35px;
            flex-wrap: wrap;
        }

        .story-links a {
            padding: 14px 27px;
            background: #294e40;
            color: white;
            text-decoration: none;
            transition: background .3s;
        }

        .story-links a:hover {
            background: #1d392f;
        }

        .values-section {
            background: #eeece5;
            padding: 75px 25px;
            text-align: center;
        }

        .values-section h2 {
            font-size: clamp(28px, 4vw, 36px);
            font-weight: normal;
            margin: 20px 0 45px;
        }

        .values-container {
            max-width: 1050px;
            margin: auto;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        .value-item {
            padding: 25px 20px;
        }

        .value-item h3 {
            font-size: 19px;
            font-weight: 500;
            margin-bottom: 15px;
        }

        .value-item p {
            color: #73776f;
            line-height: 1.8;
            font-size: 14px;
        }

        .story-footer {
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e3e0d8;
            font-size: 12px;
            color: #73776f;
        }

        @media (max-width: 700px) {
            .values-container {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .story-hero {
                min-height: 430px;
            }

            .story-content {
                padding: 65px 20px;
            }
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
        <a href="our_story.php" class="active">Our Story</a>
        <a href="order_history.php">My Orders</a>
    </nav>

</header>

<section class="story-hero">
    <div class="story-hero-content">

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

    <p>
        Our goal is to help you discover furniture that
        reflects your style and makes every corner of
        your home feel more personal.
    </p>

    <div class="story-links">
        <a href="living.php">Explore Living</a>
        <a href="dining.php">Explore Dining</a>
    </div>

</section>

<section class="values-section">

    <span>WHAT WE BELIEVE IN</span>

    <h2>The values behind NESTORA</h2>

    <div class="values-container">

        <div class="value-item">
            <h3>Thoughtful Design</h3>
            <p>
                We believe every furniture piece should
                have a purpose and bring something
                meaningful to your space.
            </p>
        </div>

        <div class="value-item">
            <h3>Everyday Comfort</h3>
            <p>
                A home should feel welcoming. We value
                comfort and furniture that makes daily
                living more enjoyable.
            </p>
        </div>

        <div class="value-item">
            <h3>Personal Expression</h3>
            <p>
                Your home reflects who you are. We want
                to help you create spaces that express
                your personality and style.
            </p>
        </div>

    </div>

</section>

<footer class="story-footer">
    © 2026 NESTORA · Furniture for a considered life.
</footer>

</body>
</html>
