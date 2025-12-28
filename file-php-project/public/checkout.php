<?php
ob_start();//error

include '../includes/header.php';
include '../Classes/database.php';
include '../Classes/checkout.php';



$database   = Database::getInstance();
$connection = $database->getConnection();
$checkout   = new Checkout($connection);

$user_id = $_SESSION['user_id'];


$user = $checkout->getUser($user_id);
if (!$user) {
    die("User not found");
}

$userName     = $user['first_name'] . ' ' . $user['last_name'];
$userEmail    = $user['email'];
$userLocation = $user['location'] ?? '';

$cartItems = $checkout->getCartItems($user_id);
if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}


$totals = $checkout->calculateTotals($cartItems);
?>

<div class="container mt-4 mb-5">
    <h2 class="mb-4"><i class="fas fa-credit-card"></i> Checkout</h2>

    <div class="row">
        <!-- order summary -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">

                    <?php foreach ($cartItems as $item): ?>
                        <div class="d-flex mb-3">
                            <img src="assets/images/<?= htmlspecialchars($item['image']) ?>"
                                 width="60" height="60" class="rounded me-3">
                            <div class="flex-grow-1">
                                <strong><?= htmlspecialchars($item['product_name']) ?></strong><br>
                                <small>
                                    $<?= number_format($item['price'],2) ?> × <?= $item['quantity'] ?>
                                </small>
                            </div>
                            <strong>
                                $<?= number_format($item['subtotal'],2) ?>
                            </strong>
                        </div>
                    <?php endforeach; ?>

                    <hr>

                    <p>Subtotal: $<?= number_format($totals['subtotal'],2) ?></p>
                    <p>Shipping: $<?= number_format($totals['shipping'],2) ?></p>
                    <p>Tax: $<?= number_format($totals['tax'],2) ?></p>

                    <hr>
                    <h5 class="text-success">
                        Total: $<?= number_format($totals['total'],2) ?>
                    </h5>
                </div>
            </div>
        </div>

        <!-- Payment -->
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5>Shipping & Payment</h5>
                </div>
                <div class="card-body">

                    <form action="process_order.php" method="POST" id="checkoutForm">

                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control mb-2"
                               name="full_name"
                               value="<?= htmlspecialchars($userName) ?>" required>

                        <label class="form-label">Email</label>
                        <input type="email" class="form-control mb-2"
                               value="<?= htmlspecialchars($userEmail) ?>" readonly>

                        <label class="form-label">Address</label>
                        <textarea class="form-control mb-2"
                                  name="address"
                                  required><?= htmlspecialchars($userLocation) ?></textarea>

                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select mb-3" required>
                            <option value="">Choose...</option>
                            <option value="card">Card</option>
                            <option value="cash">Cash</option>
                            <option value="paypal">PayPal</option>
                        </select>

                        <button class="btn btn-success w-100">
                            Place Order ($<?= number_format($totals['total'],2) ?>)
                        </button>

                        <a href="cart.php" class="btn btn-outline-secondary w-100 mt-2">
                            Back to Cart
                        </a>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<?php
include '../includes/footer.php';
ob_end_flush();
?>
