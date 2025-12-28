<?php
session_start();
include '../classes/database.php';
include '../classes/Cart.php';

$database = Database::getInstance();
$connection = $database->getConnection();
$cartClass = new Cart($connection);
$user_id = $_SESSION['user_id'] ?? null;

if ($user_id) {
    try {
        $cartClass->clear($user_id);
        header('Location: cart.php?success=cleared');
        exit;
    }
     catch (Exception $e) {
        header('Location: cart.php?error=database');
        exit;
    }
} 
else {
    // guest
    unset($_SESSION['guest_cart']);
    header('Location: cart.php?success=cleared');
    exit;
}
