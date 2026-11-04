<?php
include '../config/database.php';
include '../includes/header.php';
include '../includes/sidebar.php';

if (isset($_POST['save'])) {

    // Get form information
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];

    // Get image information
    $image = $_FILES['image']['name'];
    $tmp_name = $_FILES['image']['tmp_name'];

    // Absolute image destination folder path and auto-creation
    $folder = __DIR__ . "/../assets/images/furniture/";
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    // Generate unique image name
    $extension = strtolower(pathinfo($image, PATHINFO_EXTENSION));
    $new_image = uniqid() . "." . $extension;

    // Allowed image extensions
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (
        empty($name) ||
        empty($description) ||
        !is_numeric($price) ||
        $price < 0 ||
        filter_var($quantity, FILTER_VALIDATE_INT) === false ||
        $quantity < 0
    ) {
        $error_message = "Please enter valid information.";
    }
    elseif (!in_array($extension, $allowed)) {
        $error_message = "Only JPG, JPEG, PNG and WEBP images are allowed.";
    }
    elseif ($_FILES['image']['error'] !== UPLOAD_ERR_OK || $_FILES['image']['size'] > (2 * 1024 * 1024)) {
        $error_message = "Image upload failed or exceeds 2MB.";
    }
    else {

        // Upload image
        if (move_uploaded_file($tmp_name, $folder . $new_image)) {

            // Insert furniture into database using PDO prepared statements
            try {
                $sql = "INSERT INTO furniture (furniture_name, description, price, quantity, image) 
                        VALUES (:furniture_name, :description, :price, :quantity, :image)";
                $stmt = $conn->prepare($sql);
                $success = $stmt->execute([
                    ':furniture_name' => $name,
                    ':description' => $description,
                    ':price' => $price,
                    ':quantity' => $quantity,
                    ':image' => $new_image
                ]);

                if ($success) {
                    $success_message = "Furniture registered successfully!";
                }
            } catch (PDOException $e) {
                if (file_exists($folder . $new_image)) {
                    unlink($folder . $new_image);
                }
                $error_message = "Error: " . $e->getMessage();
            }

        } else {
            $error_message = "Image upload failed.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Furniture Registration - Form & Field</title>
    <style>
        /* Figma "Form & Field" Aesthetic Styling */
        body {
            background-color: #f7f4ee !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2c2c2c;
            margin: 0;
        }

        /* Reduced left margin to close the gap next to the sidebar */
        .content-container {
            margin-left: 175px; 
            padding: 25px 30px;
            box-sizing: border-box;
        }

        .page-header-title {
            font-family: Georgia, serif;
            font-size: 28px;
            font-weight: 400;
            color: #1a1a1a;
            margin-bottom: 4px;
        }

        .page-subtitle {
            color: #6c665e;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .form-layout-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 20px;
            max-width: 1250px;
        }

        @media (max-width: 1100px) {
            .form-layout-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 22px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            border: 1px solid #ede8e1;
        }

        .form-card h3 {
            font-family: Georgia, serif;
            font-size: 17px;
            font-weight: 400;
            margin-bottom: 18px;
            color: #1a1a1a;
            border-bottom: 1px solid #f0eae1;
            padding-bottom: 8px;
        }

        .field-group {
            margin-bottom: 16px;
        }

        .field-group label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: #3a3835;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .field-group input[type="text"],
        .field-group input[type="number"],
        .field-group textarea {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #dcd4c7;
            border-radius: 6px;
            font-size: 13px;
            background-color: #fbf9f6;
            color: #2c2c2c;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }

        .field-group input:focus,
        .field-group textarea:focus {
            border-color: #2d4a3e;
            background-color: #ffffff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(45, 74, 62, 0.1);
        }

        .field-group textarea {
            height: 85px;
            resize: vertical;
        }

        .upload-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 22px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            border: 1px solid #ede8e1;
        }

        .upload-box {
            border: 2px dashed #dcd4c7;
            border-radius: 8px;
            padding: 16px 12px;
            background-color: #fbf9f6;
            text-align: center;
        }

        .btn-save-furniture {
            background-color: #2d4a3e;
            color: #ffffff;
            padding: 11px 22px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            width: 100%;
        }

        .btn-save-furniture:hover {
            background-color: #1e332a;
        }
    </style>
</head>
<body>

<div class="content-container">
    <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #6c665e; margin-bottom: 4px;">
        Furniture / Registration
    </div>
    <h1 class="page-header-title">Furniture details</h1>
    <div class="page-subtitle">Keep the details as thoughtful as the piece itself. Fields marked * are required.</div>

    <?php if (!empty($error_message)): ?>
        <p style="color:red; background: #ffe6e6; padding: 10px; border-radius: 6px;"><?php echo $error_message; ?></p>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <p style="color:#2d4a3e; background: #e6f4ea; padding: 10px; border-radius: 6px; font-weight: 600;"><?php echo $success_message; ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-layout-grid">
            
            <!-- Left Column: The Essentials -->
            <div class="form-card">
                <h3>The essentials</h3>

                <div class="field-group">
                    <label for="name">Furniture name *</label>
                    <input type="text" id="name" name="name" placeholder="e.g. Arden lounge chair" required>
                </div>

                <div class="field-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" placeholder="Describe the materials, craft, and feel..." required></textarea>
                </div>

                <div class="field-group">
                    <label for="price">Price (USD) *</label>
                    <input type="number" id="price" name="price" min="0" step="0.01" placeholder="0.00" required>
                </div>

                <div class="field-group">
                    <label for="quantity">Quantity in stock *</label>
                    <input type="number" id="quantity" name="quantity" min="0" placeholder="1" required>
                </div>
            </div>

            <!-- Right Column: Product Photograph & Submit -->
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <div class="upload-card">
                    <h3 style="font-family: Georgia, serif; font-size: 16px; margin-bottom: 12px; border-bottom: 1px solid #f0eae1; padding-bottom: 8px;">Product photograph *</h3>
                    
                    <div class="upload-box">
                        <p style="margin-bottom: 4px; font-weight: 500; font-size: 12px; color: #4a453f;">Upload or replace image</p>
                        <p style="font-size: 10px; color: #8c857b; margin-bottom: 10px;">JPG, PNG or WEBP up to 2MB</p>
                        <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" required style="font-size: 11px; width: 100%;">
                    </div>
                </div>

                <div>
                    <button type="submit" name="save" class="btn-save-furniture">Save furniture</button>
                </div>
            </div>

        </div>
    </form>
</div>

<?php 
// Include footer at the bottom of the page layout
include '../includes/footer.php'; 
?>

</body>
</html>