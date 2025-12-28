<?php
ob_start(); 

include '../classes/database.php';
require '../includes/auth.php';


$database = Database::getInstance();
$connection = $database->getConnection();


if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_message = "Please fill all fields!";
    }
     elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format!";
    } 
    
    else {
        
    try {
          $stmt = $connection->prepare("SELECT * FROM users WHERE email = :email");
          $stmt->bindParam(':email', $email);
          $stmt->execute();
          $user = $stmt->fetch(PDO::FETCH_ASSOC);

          
         if ($user && password_verify($password, $user['password'])) {

           $_SESSION['user_id'] = $user['user_id'];
           $_SESSION['first_name'] = $user['first_name'];
           $_SESSION['last_name'] = $user['last_name'];
           $_SESSION['email'] = $user['email'];
           $_SESSION['role'] = $user['role'];

            $user_id = $user['user_id'];

            
         if (isset($_SESSION['guest_cart']) && !empty($_SESSION['guest_cart'])) {
                    
                   
             $stmt = $connection->prepare("SELECT cart_id FROM cart WHERE user_id = :user_id");
             $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
             $stmt->execute();
             $cart = $stmt->fetch(PDO::FETCH_ASSOC);

         if (!$cart) {
                        
                 $stmt = $connection->prepare("INSERT INTO cart (user_id) VALUES (:user_id)");
                 $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                 $stmt->execute();
                 $cart_id = $connection->lastInsertId();

                    } 
                    
         else {
                        $cart_id = $cart['cart_id'];
                    }

                
     foreach ($_SESSION['guest_cart'] as $product_id => $quantity) {
                        
         $stmt = $connection->prepare("SELECT price FROM products WHERE product_id = :product_id");
         $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
         $stmt->execute();
         $product = $stmt->fetch(PDO::FETCH_ASSOC);

         if ($product) {
                    
        $price = $product['price'];
        $stmt = $connection->prepare(
                            "
                               SELECT cart_item_id, quantity 
                                FROM cart_items 
                                WHERE cart_id = :cart_id AND product_id = :product_id
                            ");

        $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_item) {
                               
              $new_quantity = $existing_item['quantity'] + $quantity;
              $stmt = $connection->prepare("
                                    UPDATE cart_items 
                                    SET quantity = :quantity 
                                    WHERE cart_item_id = :cart_item_id
                                ");
               $stmt->bindParam(':quantity', $new_quantity, PDO::PARAM_INT);
               $stmt->bindParam(':cart_item_id', $existing_item['cart_item_id'], PDO::PARAM_INT);
               $stmt->execute();
                            } 
                            
                            
            else {
                             
                $stmt = $connection->prepare("
                                    INSERT INTO cart_items (cart_id, product_id, quantity, price) 
                                    VALUES (:cart_id, :product_id, :quantity, :price)");
                $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
                $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
                $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                $stmt->bindParam(':price', $price);
                $stmt->execute();
                            }
                        }
                    }

                   
                    unset($_SESSION['guest_cart']);
                }

                ob_end_clean();
        if ($user['role'] === 'admin') {
                    header('Location: ../admin/admindashboard.php');
                } 
        else {
                    header('Location: index.php');
                }
                exit;

            } 
        else {
                $error_message = "Email or password is incorrect!";
            }

        } 
        catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-4">
                    <h3 class="mb-0"><i class="fas fa-sign-in-alt"></i> Login</h3>
                    <p class="mb-0">Sign in to your account</p>
                </div>
                <div class="card-body">

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email"
                                   class="form-control"
                                   id="email"
                                   name="email"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password"
                                   class="form-control"
                                   id="password"
                                   name="password"
                                   required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-3">
                        <p class="mb-0">Don't have an account? 
                            <a href="register.php" class="text-decoration-none">Register here</a>
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php 
include '../includes/footer.php';
ob_end_flush();
?>