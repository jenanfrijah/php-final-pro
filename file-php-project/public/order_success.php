<?php
ob_start();
include '../includes/header.php';
include '../classes/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header('Location: index.php');
    exit;
}

$database = Database::getInstance();
$connection = $database->getConnection();
$user_id = $_SESSION['user_id'];
$order_id = intval($_GET['order_id']);

try {
   //fetch order details
    $stmt = $connection->prepare("
        SELECT o.*, p.payment_method, p.payment_status
        FROM orders o
        LEFT JOIN payments p ON o.order_id = p.order_id
        WHERE o.order_id = :order_id AND o.user_id = :user_id ");

    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("Order not found!");
    }

  } 

catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 80px;"></i>
                    </div>
                    
                    <h2 class="text-success mb-3">Order Placed Successfully!</h2>
                    
                    <p class="lead mb-4">
                        Thank you for your purchase. Your order has been received and is being processed.
                    </p>

                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h5 class="mb-3">Order Details</h5>
                            <div class="row text-start">
                                <div class="col-6">
                                    <p class="mb-2"><strong>Order ID:</strong></p>
                                    <p class="mb-2"><strong>Date:</strong></p>
                                    <p class="mb-2"><strong>Total:</strong></p>
                                    <p class="mb-2"><strong>Status:</strong></p>
                                    <p class="mb-0"><strong>Payment Method:</strong></p>
                                </div>
                                <div class="col-6">
                                    <p class="mb-2">#<?= $order['order_id'] ?></p>
                                    <p class="mb-2"><?= date('M d, Y', strtotime($order['created_at'])) ?></p>
                                    <p class="mb-2 text-success">$<?= number_format($order['total_price'], 2) ?></p>
                                    <p class="mb-2">
                                        <span class="badge bg-warning"><?= ucfirst($order['status']) ?></span>
                                    </p>
                                    <p class="mb-0"><?= ucwords(str_replace('_', ' ', $order['payment_method'])) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        A confirmation email has been sent to your registered email address.
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="index.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-home"></i> Continue Shopping
                        </a>
                        <a href="profile.php" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-user"></i> My Orders
                        </a>
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