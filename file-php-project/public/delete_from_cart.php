<?php
session_start();
include '../classes/database.php';
include '../classes/Cart.php';

if (isset($_GET['cart_item_id'])) {
    $cart_item_id = intval($_GET['cart_item_id']);
    $user_id = $_SESSION['user_id'] ?? null;

 if ($user_id) {
       //if is logged in delete from db
     try {
            $database = Database::getInstance();
            $connection = $database->getConnection();
            $cartClass = new Cart($connection);
            $cartClass->removeItem($cart_item_id);

            header('Location: cart.php?success=removed');
            exit;

        } catch (Exception $e) {
            header('Location: cart.php?error=database');
            exit;
        }

    } 
    
    else {
        //if guset delete from session
        if (isset($_SESSION['guest_cart'][$cart_item_id])) {
            unset($_SESSION['guest_cart'][$cart_item_id]);
        }
        header('Location: cart.php?success=removed');
        exit;
    }
}

header('Location: cart.php');
exit;
