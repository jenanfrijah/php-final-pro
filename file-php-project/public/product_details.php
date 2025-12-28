<?php
ob_start();
include '../includes/header.php';
include '../Classes/database.php';
include '../Classes/product.php';

$database   = Database::getInstance();
$connection = $database->getConnection();
$productObj = new Product($connection);


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid product ID");
}

$product_id = (int) $_GET['id'];
$product    = $productObj->getProductById($product_id);

if (!$product) {
    die("Product not found");
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
          $quantity = (int) ($_POST['quantity'] ?? 1);
          $user_id  = $_SESSION['user_id'] ?? null;

          $productObj->addToCart($product_id, $quantity, $user_id);

          header("Location: cart.php");
          exit;
    } 

    catch (Exception $e) {
        $error = $e->getMessage();
    }
    
}

?>

<div class="container mt-4">
  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="row">
    <div class="col-md-6">
      <img src="assets/images/<?= htmlspecialchars($product['image']) ?>"
           class="img-fluid rounded shadow"
           alt="<?= htmlspecialchars($product['product_name']) ?>">
    </div>

    <div class="col-md-6">
      <h2><?= htmlspecialchars($product['product_name']) ?></h2>
      <p class="text-success fs-3 fw-bold">
        $<?= number_format($product['price'], 2) ?>
      </p>
      <p><?= htmlspecialchars($product['description']) ?></p>

      <?php if ($product['stock'] > 0): ?>
        <form method="POST">
          <label class="form-label">Quantity</label>
          <input type="number"
                 name="quantity"
                 value="1"
                 min="1"
                 max="<?= $product['stock'] ?>"
                 class="form-control mb-3"
                 style="width:120px">

          <button class="btn btn-success">Add to Cart</button>
          <a href="index.php" class="btn btn-secondary">Back</a>
        </form>
      <?php else: ?>
        <span class="badge bg-danger">Out of stock</span>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
include '../includes/footer.php';
ob_end_flush();
?>
