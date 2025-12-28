<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include '../includes/header.php';
include '../Classes/database.php';
include '../Classes/user_orders.php';

$database = Database::getInstance();
$connection = $database->getConnection();
$user_id = $_SESSION['user_id'];

$orderClass = new Order($connection);

$orders = [];

try {
    $orders = $orderClass->getUserOrders($user_id);
} catch (Exception $e) {
    $error = $e->getMessage();
}


$selected_product_id = $_GET['product_id'] ?? null;
?>

<div class="container mt-5">
    <h2 class="mb-4">My Orders</h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php elseif (empty($orders)): ?>
        <div class="alert alert-info">
            No Orders yet
        </div>

    <?php else: ?>
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Order ID</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Order Date</th>
                    <th>Rate</th>
                </tr>
            </thead>
            <tbody>

            <?php foreach ($orders as $index => $order): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($order['order_id']) ?></td>
                    <td><?= htmlspecialchars($order['total_price']) ?> JD</td>
                    <td><?= htmlspecialchars($order['status']) ?></td>
                    <td><?= htmlspecialchars($order['created_at']) ?></td>
                    <td>
                       

                            <?php foreach ($order['products'] as $product): ?>
                                <a href="?product_id=<?= $product['product_id'] ?>" class="btn btn-warning btn-sm mb-1">
                                    Rate 
                                </a><br>
                            <?php endforeach; ?>
                       
                        
                    </td>
                </tr>
            <?php endforeach; ?>

            </tbody>
        </table>
    <?php endif; ?>


    <?php if ($selected_product_id): //rating form ?>
        <div class="card mt-5">
            <div class="card-body">
                <form method="POST" action="submit_review.php">
                    <input type="hidden" name="product_id" value="<?= $selected_product_id ?>">

                    <div class="mb-3">
                        <label class="form-label">Rating</label>
                        <select name="rating" class="form-select" required>
                            <option value="5">⭐⭐⭐⭐⭐</option>
                            <option value="4">⭐⭐⭐⭐</option>
                            <option value="3">⭐⭐⭐</option>
                            <option value="2">⭐⭐</option>
                            <option value="1">⭐</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Comment</label>
                        <textarea name="comment" class="form-control" rows="3" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">Send Rating</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
