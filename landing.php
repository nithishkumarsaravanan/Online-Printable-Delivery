<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Landing Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 50px;
        }

        .container {
            max-width: 900px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        h1 {
            margin-bottom: 30px;
            text-align: center;
            color: #5c4ac7;
        }

        .custom-file-input~.custom-file-label::after {
            content: "Browse";
        }

        .product-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .product-image {
            width: 150px;
            height: 150px;
            background-size: cover;
            background-position: center;
            border-radius: 8px;
            margin-right: 20px;
        }

        .product-details {
            flex-grow: 1;
        }

        .quantity-input {
            width: 100px;
        }
    </style>
</head>

<body>
    <?php
    session_start();
    include("connection/connect.php");

    $res_id = isset($_GET['res_id']) ? intval($_GET['res_id']) : 0;
    ?>

    <header id="header" class="header-scroll top-header headrom">
        <nav class="navbar navbar-dark">
            <div class="container">
                <button class="navbar-toggler hidden-lg-up" type="button" data-toggle="collapse" data-target="#mainNavbarCollapse">&#9776;</button>
                <a class="navbar-brand" href="index.php"> <img class="img-rounded" src="images/logo.png" alt=""> </a>
                <div class="collapse navbar-toggleable-md  float-lg-right" id="mainNavbarCollapse">
                    <ul class="nav navbar-nav">
                        <li class="nav-item"> <a class="nav-link active" href="index.php">Home <span class="sr-only">(current)</span></a> </li>
                        <li class="nav-item"> <a class="nav-link active" href="stores.php">Stores <span class="sr-only"></span></a> </li>

                        <?php
                        if (empty($_SESSION["user_id"])) {
                            echo '<li class="nav-item"><a href="login.php" class="nav-link active">Login</a> </li>
                                  <li class="nav-item"><a href="registration.php" class="nav-link active">Register</a> </li>';
                        } else {
                            echo '<li class="nav-item"><a href="your_orders.php" class="nav-link active">My Orders</a> </li>';
                            echo '<li class="nav-item"><a href="logout.php" class="nav-link active">Logout</a> </li>';
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1>Product Details</h1>

        <?php
        if ($res_id > 0) {
            $stmt = $db->prepare("SELECT * FROM print_photo WHERE d_id = ?");
            $stmt->bind_param("i", $res_id);
            $stmt->execute();
            $result = $stmt->get_result();
        } elseif (isset($_GET['upload']) && $_GET['upload'] === 'success') {
            // Fetch latest product if upload success and no res_id
            $result = $db->query("SELECT * FROM print_photo ORDER BY d_id DESC LIMIT 1");
        } else {
            $result = false;
        }

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $imgPath = "admin/Res_img/prints/" . htmlspecialchars($row['img']);
            $title = htmlspecialchars($row['title']);
            $slogan = htmlspecialchars($row['slogan']);
            $price = htmlspecialchars($row['price']);
            echo '<div class="product-card">';
            echo '<div class="product-image" style="background-image: url(\'' . $imgPath . '\');"></div>';
            echo '<div class="product-details">';
            echo '<h4>' . $title . '</h4>';
            echo '<p>' . $slogan . '</p>';
            echo '<p><strong>Price: ₹' . $price . '</strong></p>';
            echo '<label for="quantity">Quantity:</label> ';
            echo '<input type="number" id="quantity" name="quantity" class="form-control quantity-input" value="1" min="1" />';
            echo '</div>';
            echo '</div>';
        } else {
            if (isset($_GET['upload']) && $_GET['upload'] === 'success') {
                // If upload success but no product found
                echo '<p>Upload successful. No product details available.</p>';
            } elseif ($res_id > 0) {
                echo '<p>Product not found.</p>';
            } else {
                echo '<p>No product specified.</p>';
            }
        }
        ?>

        <?php if (isset($_GET['upload']) && $_GET['upload'] === 'success'): ?>
            <script>
                alert('Uploaded successfully');
            </script>
        <?php endif; ?>

        <form action="upload.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="res_id" value="<?php echo htmlspecialchars($res_id); ?>" />
            <div class="form-group">
                <label for="fileInput">Choose file to upload (PDF, images, etc.)</label>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="fileInput" name="uploadedFile" accept=".pdf,image/*" required />
                    <label class="custom-file-label" for="fileInput">Choose file</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Upload</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Show selected file name in the label
        $('.custom-file-input').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName);
        });
    </script>
</body>

</html>