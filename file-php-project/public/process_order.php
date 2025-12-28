<?php
ob_start();
session_start();
include '../classes/database.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

$database = Database::getInstance();
$connection = $database->getConnection();
$user_id = $_SESSION['user_id'];


$full_name = trim($_POST['full_name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$address = trim($_POST['address']);
$city = trim($_POST['city']);
$payment_method = $_POST['payment_method'];
$notes = trim($_POST['notes'] ?? '');

$full_address = $address . ', ' . $city;

try {
  
    $connection->beginTransaction();

    $stmt = $connection->prepare("SELECT cart_id FROM cart WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cart) {
        throw new Exception("Cart not found!");
    }

    $cart_id = $cart['cart_id'];

    
    $stmt = $connection->prepare("
        SELECT 
            ci.product_id,
            ci.quantity,
            ci.price,
            p.stock,
            (ci.quantity * ci.price) AS subtotal
        FROM cart_items ci
        INNER JOIN products p ON ci.product_id = p.product_id
        WHERE ci.cart_id = :cart_id
    ");

    $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
    $stmt->execute();
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartItems)) {
        throw new Exception("Your cart is empty!");
    }

 
    $subtotal = 0;

    foreach ($cartItems as $item) {
        if ($item['quantity'] > $item['stock']) {
            throw new Exception("Not enough stock for product ID: " . $item['product_id']);
        }
          $subtotal += $item['subtotal'];
    }

    $shipping_cost = 5.00;
    $tax = $subtotal * 0.10;
    $total_price = $subtotal + $shipping_cost + $tax;


    $stmt = $connection->prepare("
        INSERT INTO orders (user_id, total_price,status,created_at)
        VALUES (:user_id, :total_price, 'pending',NOW())
    ");
    
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':total_price', $total_price);
    $stmt->execute();

    $order_id = $connection->lastInsertId();

    foreach ($cartItems as $item) {
        $stmt = $connection->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price)
            VALUES (:order_id, :product_id, :quantity, :price)
        ");

        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
        $stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
        $stmt->bindParam(':price', $item['price']);
        $stmt->execute();

    
        $new_stock = $item['stock'] - $item['quantity'];
        $stmt = $connection->prepare("
            UPDATE products 
            SET stock = :stock 
            WHERE product_id = :product_id
        ");

        $stmt->bindParam(':stock', $new_stock, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
        $stmt->execute();
    }

    $stmt = $connection->prepare("
        INSERT INTO payments (
            order_id, 
            amount, 
            payment_method, 
            payment_status,
            payment_date
        ) VALUES (
            :order_id, 
            :amount, 
            :payment_method, 
            'pending',
            NOW()
        )
    ");

    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->bindParam(':amount', $total_price);
    $stmt->bindParam(':payment_method', $payment_method);
    $stmt->execute();

   
    $stmt = $connection->prepare("
        UPDATE users 
        SET location = :location 
        WHERE user_id = :user_id
    ");

    $stmt->bindParam(':location', $full_address);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

   
    $stmt = $connection->prepare("DELETE FROM cart_items WHERE cart_id = :cart_id");
    $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
    $stmt->execute();

    
    $connection->commit();

    
    ob_end_clean();
    header("Location: order_success.php?order_id=" . $order_id);
    exit;

} 

catch (Exception $e) {
 
    $connection->rollBack();
    
    ob_end_clean();
    header("Location: checkout.php?error=" . urlencode($e->getMessage()));
    exit;
}
?>