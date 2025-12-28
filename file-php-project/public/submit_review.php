<?php
session_start();
include '../Classes/database.php';
include '../Classes/reviews.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']); 
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    if (empty($comment)) {
        die('Invalid data');
    }

    $database = Database::getInstance();
    $connection = $database->getConnection();

    $reviewClass = new Review($connection);

 
    // if ($reviewClass->hasUserReviewed($user_id, $product_id)) {
    //     die('you already rated it!');
    // }

    $success = $reviewClass->addReview($user_id, $product_id, $rating, $comment);

    if ($success) {
        header("Location: user_orders.php");  
        exit;
    }

    else {
        die('Rating process is failed');
    }


}
