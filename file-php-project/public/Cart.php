<?php
ob_start();
include '../includes/header.php';
include '../classes/database.php';
include '../classes/Cart.php';

$database = Database::getInstance();
$connection = $database->getConnection();

$user_id = $_SESSION['user_id'] ?? null;

$cartClass = new Cart($connection);

$cartItems = [];
$total = 0;

if ($user_id) {
   //if is logged in
    try {
        $cartItems = $cartClass->getItems($user_id);
        $total = $cartClass->getTotal($user_id);
    } 
    catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
} 

//guest

else {
    
$guest_cart = $_SESSION['guest_cart'] ?? [];
foreach ($guest_cart as $product_id => $quantity) {
  $stmt = $connection->prepare("SELECT * FROM products WHERE product_id = :product_id");
  $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
  $stmt->execute();
  $product = $stmt->fetch(PDO::FETCH_ASSOC);
     if ($product) {
            $cartItems[] = [
                'cart_item_id' => $product_id, 
                'product_id'   => $product['product_id'],
                'product_name' => $product['product_name'],
                'image'        => $product['image'],
                'price'        => $product['price'],
                'quantity'     => $quantity,
                'item_total'   => $product['price'] * $quantity,
                'stock'        => $product['stock']
            ];
            $total += $product['price'] * $quantity;
              }
           }
         }
?>

<div class="container mt-4">
    <h2 class="mb-4">
        <i class="fas fa-shopping-cart"></i> Your Shopping Cart
    </h2>

    <?php if (empty($cartItems)): ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle fa-3x mb-3"></i>
            <h4>Your cart is empty</h4>
            <p>Start adding some products to your cart!</p>
        </div>
        <div class="text-center">
            <a href="index.php" class="btn btn-primary btn-lg">
                <i class="fas fa-shopping-bag"></i> Continue Shopping
            </a>
        </div>

    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="assets/images/<?= htmlspecialchars($item['image']) ?>" 
                                         alt="<?= htmlspecialchars($item['product_name']) ?>"
                                         class="img-thumbnail me-3"
                                         style="width: 80px; height: 80px; object-fit: cover;"
                                         onerror="this.src='https://via.placeholder.com/80'">
                                    <div>
                                        <h6 class="mb-0">
                                            <a href="product_details.php?id=<?= $item['product_id'] ?>" 
                                               class="text-decoration-none text-dark">
                                                <?= htmlspecialchars($item['product_name']) ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted">
                                            <?php if ($item['stock'] > 0): ?>
                                                <span class="text-success">
                                                    <i class="fas fa-check-circle"></i> In Stock
                                                </span>
                                            <?php else: ?>
                                                <span class="text-danger">
                                                    <i class="fas fa-times-circle"></i> Out of Stock
                                                </span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <strong class="text-success">
                                    $<?= number_format($item['price'], 2) ?>
                                </strong>
                            </td>
                            <td>
                                <form action="cart_update.php" method="POST" class="d-inline">
                                    <input type="hidden" name="cart_item_id" value="<?= $item['cart_item_id'] ?>">
                                    <div class="input-group" style="width: 130px;">
                                        <input type="number" 
                                               name="quantity" 
                                               class="form-control form-control-sm text-center" 
                                               value="<?= $item['quantity'] ?>" 
                                               min="1" 
                                               max="<?= $item['stock'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Update">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                </form>
                            </td>
                            <td>
                                <strong class="text-primary">
                                    $<?= number_format($item['item_total'], 2) ?>
                                </strong>
                            </td>
                            <td>
                                <a href="delete_from_cart.php?cart_item_id=<?= $item['cart_item_id'] ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Are you sure you want to remove this item?')">
                                    <i class="fas fa-trash-alt"></i> Remove
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <td colspan="3" class="text-end">
                            <h5 class="mb-0">Total:</h5>
                        </td>
                        <td colspan="2">
                            <h5 class="text-success mb-0">
                                $<?= number_format($total, 2) ?>
                            </h5>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Continue Shopping
            </a>
            
            <div>
                <a href="clear_cart.php" 
                   class="btn btn-outline-danger me-2"
                   onclick="return confirm('Are you sure you want to clear your cart?')">
                    <i class="fas fa-trash"></i> Clear Cart
                </a>
                <a href="<?= $user_id ? 'checkout.php' : 'login.php' ?>" class="btn btn-success btn-lg">
                    <i class="fas fa-credit-card"></i> Proceed to Checkout
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php 
include '../includes/footer.php';
ob_end_flush();
?>
