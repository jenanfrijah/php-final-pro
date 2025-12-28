<?php
session_start();
include '../classes/database.php';
include '../classes/Cart.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_item_id'], $_POST['quantity'])) {

    $cart_item_id = intval($_POST['cart_item_id']);
    $quantity = intval($_POST['quantity']);
    $user_id = $_SESSION['user_id'] ?? null;

    if ($user_id) {
       
     try {

        $database = Database::getInstance();
        $connection = $database->getConnection();
        $cartClass = new Cart($connection);
        $cartClass->updateQuantity($cart_item_id, $quantity);

         header('Location: cart.php?success=updated');
            exit;

        } 
        
        catch (Exception $e) {
            header('Location: cart.php?error=' . urlencode($e->getMessage()));
            exit;
        }

    } 
    
    else {
        // guest
        if ($quantity > 0) {
            $_SESSION['guest_cart'][$cart_item_id] = $quantity;
        } 
        else {
            unset($_SESSION['guest_cart'][$cart_item_id]);
        }
        header('Location: cart.php?success=updated');
        exit;
    }
}

header('Location: cart.php');
exit;
