<?php
session_start();
error_reporting(0);
include("connection/connect.php");
include_once 'product-action.php';

if (empty($_SESSION["user_id"])) {
    header('Location: login.php');
    exit();
}

function function_alert()
{
    echo "<script>alert('Thank you. Your Order has been placed!');</script>";
    echo "<script>window.location.replace('your_orders.php');</script>";
}

$item_total = 0;
$res_id = isset($_GET['res_id']) ? intval($_GET['res_id']) : 0;
$quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
$price = 0;

if ($res_id > 0) {
    // Add product to cart session if not already added
    if (!isset($_SESSION["cart_item"]) || !array_key_exists($res_id, $_SESSION["cart_item"])) {
        $stmt = $db->prepare("SELECT * FROM print_photo WHERE d_id = ?");
        $stmt->bind_param("i", $res_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $price = floatval($row['price']);
            $title = $row['title'];
            $itemArray = array($res_id => array('title' => $title, 'd_id' => $res_id, 'quantity' => $quantity, 'price' => $price));
            if (!isset($_SESSION["cart_item"])) {
                $_SESSION["cart_item"] = $itemArray;
            } else {
                $_SESSION["cart_item"] = $_SESSION["cart_item"] + $itemArray;
            }
        }
    } else {
        // If already in cart, update quantity
        $_SESSION["cart_item"][$res_id]["quantity"] += $quantity;
        $price = $_SESSION["cart_item"][$res_id]["price"];
    }
} else {
    $price = 0;
}

$item_total = $price * $quantity;

// Handle delivery address form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_address'])) {
    $address_line = trim($_POST['address_line']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $zip = trim($_POST['zip']);
    $full_address = $db->real_escape_string($address_line . ', ' . $city . ', ' . $state . ' - ' . $zip);
    $user_id = $_SESSION["user_id"];
    $stmt_update = $db->prepare("UPDATE users SET delivery_address = ? WHERE id = ?");
    $stmt_update->bind_param("si", $full_address, $user_id);
    $stmt_update->execute();
    // Update delivery_address variable to show updated address
    $delivery_address = htmlspecialchars($full_address);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $SQL = "insert into users_orders(u_id,title,quantity,price) values('" . $_SESSION["user_id"] . "', ?, ?, ?)";
    $stmt = $db->prepare($SQL);
    foreach ($_SESSION["cart_item"] as $item) {
        $stmt->bind_param("sid", $item["title"], $item["quantity"], $item["price"]);
        $stmt->execute();
    }
    unset($_SESSION["cart_item"]);
    $success = "Thank you. Your order has been placed!";
    function_alert();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="#">
    <title>Checkout</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="css/animsition.min.css" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>

<body>

    <div class="site-wrapper">
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


                                echo  '<li class="nav-item"><a href="your_orders.php" class="nav-link active">My Orders</a> </li>';
                                echo  '<li class="nav-item"><a href="logout.php" class="nav-link active">Logout</a> </li>';
                            }

                            ?>

                        </ul>
                    </div>
                </div>
            </nav>
        </header>
        <div class="page-wrapper">
            <div class="top-links">
                <div class="container">
                    <ul class="row links">

                        <li class="col-xs-12 col-sm-4 link-item"><span>1</span><a href="stores.php">Choose store</a></li>
                        <li class="col-xs-12 col-sm-4 link-item "><span>2</span><a href="#">Pick Your favorite Product</a></li>
                        <li class="col-xs-12 col-sm-4 link-item active"><span>3</span><a href="checkout.php">Order and Pay</a></li>
                    </ul>
                </div>
            </div>

            <div class="container">

                <span style="color:green;">
                    <?php echo $success; ?>
                </span>

                <div class="delivery-address" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9;">
                    <h5>Delivery Address</h5>
                    <p><?php echo $delivery_address ? $delivery_address : 'No delivery address found.'; ?></p>
                    <button id="addAddressBtn" class="btn btn-primary btn-sm" style="margin-top:10px;">Add Delivery Address</button>
                    <div id="addressFormContainer" style="display:none; margin-top:15px;">
                        <form method="post" action="">
                            <div class="form-group">
                                <label for="address_line">Address Line</label>
                                <input type="text" class="form-control" id="address_line" name="address_line" required>
                            </div>
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" class="form-control" id="city" name="city" required>
                            </div>
                            <div class="form-group">
                                <label for="state">State</label>
                                <input type="text" class="form-control" id="state" name="state" required>
                            </div>
                            <div class="form-group">
                                <label for="zip">Zip Code</label>
                                <input type="text" class="form-control" id="zip" name="zip" required>
                            </div>
                            <button type="submit" name="save_address" class="btn btn-success btn-sm">Save Address</button>
                        </form>
                    </div>
                </div>

            </div>




            <div class="container m-t-30">
                <form action="" method="post">
                    <div class="widget clearfix">

                        <div class="widget-body">
                            <div class="row">

                                <div class="col-sm-12">
                                    <div class="cart-totals margin-b-20">
                                        <div class="cart-totals-title">
                                            <h4>Cart Summary</h4>
                                        </div>
                                        <div class="cart-totals-fields">

                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Product</th>
                                                        <th>Quantity</th>
                                                        <th>Price</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $item_total = 0;
                                                    if (!empty($_SESSION["cart_item"])) {
                                                        foreach ($_SESSION["cart_item"] as $item) {
                                                            $item_price = $item["price"] * $item["quantity"];
                                                            $item_total += $item_price;
                                                    ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($item["title"]); ?></td>
                                                                <td><?php echo $item["quantity"]; ?></td>
                                                                <td>₹<?php echo number_format($item_price, 2); ?></td>
                                                            </tr>
                                                    <?php
                                                        }
                                                    } else {
                                                        echo '<tr><td colspan="3">Your cart is empty.</td></tr>';
                                                    }
                                                    ?>
                                                    <tr>
                                                        <td>Cart Subtotal</td>
                                                        <td></td>
                                                        <td>₹<?php echo number_format($item_total, 2); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Delivery Charges</td>
                                                        <td></td>
                                                        <td>Free</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-color"><strong>Total</strong></td>
                                                        <td></td>
                                                        <td class="text-color"><strong>₹<?php echo number_format($item_total, 2); ?></strong></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="payment-option">
                                        <ul class=" list-unstyled">
                                            <li>
                                                <label class="custom-control custom-radio  m-b-20">
                                                    <input name="mod" id="radioStacked1" checked value="COD" type="radio" class="custom-control-input"> <span class="custom-control-indicator"></span> <span class="custom-control-description">Cash on Delivery</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label class="custom-control custom-radio  m-b-10">
                                                    <input name="mod" type="radio" value="razorpay" class="custom-control-input"> <span class="custom-control-indicator"></span> <span class="custom-control-description">Razorpay <img src="images/razorpay.png" alt="" width="90"></span> </label>
                                            </li>
                                        </ul>
                                        <p class="text-xs-center"> <input type="submit" onclick="return confirm('Do you want to confirm the order?');" name="submit" class="btn btn-success btn-block" value="Order Now"> </p>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                </form>
            </div>

            <footer class="footer">
                <div class="container">
                    <div class="bottom-footer">
                        <div class="row">
                            <div class="col-xs-12 col-sm-3 payment-options color-gray">
                                <h5>Payment Options</h5>
                                <ul>
                                    <li>
                                        <a href="#">
                                            <img src="https://framerusercontent.com/images/apE2tIqb1SpkFBcRkZky8sCio.gif" alt="Razorpay" width="100">
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-xs-12 col-sm-4 address color-gray">
                                <h5>Address</h5>
                                <p>MKCE, KARUR</p>
                                <h5>Phone: 75696969855</h5>
                            </div>
                            <div class="col-xs-12 col-sm-5 additional-info color-gray">
                                <h5>Additional Information</h5>
                                <p>Join thousands of other photoshop who benefit from having partnered with us.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="js/jquery.min.js"></script>
    <script src="js/tether.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/animsition.min.js"></script>
    <script src="js/bootstrap-slider.min.js"></script>
    <script src="js/jquery.isotope.min.js"></script>
    <script src="js/headroom.js"></script>
    <script src="js/foodpicky.min.js"></script>
    <script>
        document.getElementById('addAddressBtn').addEventListener('click', function() {
            var formContainer = document.getElementById('addressFormContainer');
            if (formContainer.style.display === 'none') {
                formContainer.style.display = 'block';
                this.textContent = 'Cancel';
            } else {
                formContainer.style.display = 'none';
                this.textContent = 'Add Delivery Address';
            }
        });
    </script>
</body>

</html>
?>