<?php 

include '../includes/header.php'; 
include '../Classes/Database.php';
include '../Classes/Product.php';

$database = Database::getInstance();
$connection = $database->getConnection();
$productObj = new Product($connection);

$name_filter = $_GET['name'] ?? null;
$min_price_filter = $_GET['min_price'] ?? null;
$max_price_filter = $_GET['max_price'] ?? null;

$products = $productObj->getProducts($name_filter, $min_price_filter, $max_price_filter);
?>

<!-- Hero Section -->
<section class="bg-primary text-white py-5">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6">
        <h1 class="display-4 fw-bold">Shop the Latest Trends</h1>
        <p class="lead">Discover amazing products at unbeatable prices. Free shipping on orders over $50!</p>
        <a href="index.php" class="btn btn-light btn-lg">Shop Now</a>
      </div>
      <div class="col-lg-6 text-center">
        <img src="assets/images/hero_banner.png" alt="Hero Banner" class="img-fluid rounded shadow">
      </div>
    </div>
  </div>
</section>

<!-- Promo Banner -->
<section class="py-4 bg-warning">
  <div class="container text-center">
    <h4 class="mb-0"><i class="fas fa-gift"></i> Limited Time Offer: 20% Off All Electronics!</h4>
  </div>
</section>

<!-- Featured Products -->
<section class="py-5 bg-light">
  <div class="container">
    <h2 class="text-center mb-4">Featured Products</h2>

    <!-- Search/Filter Form -->
    <div class="row mb-4">
        <div class="col-md-10 mx-auto">
            <form method="GET" class="card card-body bg-light shadow-sm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="name" class="form-control" placeholder="Product Name" value="<?= htmlspecialchars($name_filter ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="min_price" class="form-control" placeholder="Min Price" value="<?= htmlspecialchars($min_price_filter ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="max_price" class="form-control" placeholder="Max Price" value="<?= htmlspecialchars($max_price_filter ?? '') ?>">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                         <img src="assets/images/<?= htmlspecialchars($product['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($product['product_name']) ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($product['product_name']) ?></h5>
                            <p class="text-muted"><?= htmlspecialchars(substr($product['description'], 0, 50)) ?>...</p>
                            <div class="mt-auto">
                                <p class="card-text text-success fs-5 fw-bold">$<?= number_format($product['price'], 2) ?></p>
                                <a href="product_details.php?id=<?= $product['product_id'] ?>" class="btn btn-primary w-100">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center">
                <p class="lead">No products found matching your criteria.</p>
            </div>
        <?php endif; ?>
    </div>
  </div>
</section>

<!-- Discount Section -->
<section class="py-5">
  <div class="container">
    <div class="bg-danger text-white p-5 rounded text-center">
      <h2 class="display-5 fw-bold">Flash Sale!</h2>
      <p class="lead">Up to 50% off on selected items. Limited time only.</p>
      <a href="index.php" class="btn btn-light btn-lg">Shop Deals</a>
    </div>
  </div>
</section>

<!-- Categories -->
<section class="py-5 bg-light">
  <div class="container">
    <h2 class="text-center mb-4">Shop by Category</h2>
    <div class="row text-center">
      <div class="col-md-3 mb-3">
        <a href="#" class="text-decoration-none">
          <div class="card border-0">
            <img src="assets/images/cat_electronics.jpg" class="card-img-top" alt="Electronics" style="height: 150px; object-fit: cover;">
            <div class="card-body">
              <h5 class="card-title">Electronics</h5>
            </div>
          </div>
        </a>
      </div>
      <div class="col-md-3 mb-3">
        <a href="#" class="text-decoration-none">
          <div class="card border-0">
            <img src="assets/images/cat_fashion.jpg" class="card-img-top" alt="Fashion" style="height: 150px; object-fit: cover;">
            <div class="card-body">
              <h5 class="card-title">Fashion</h5>
            </div>
          </div>
        </a>
      </div>
      <div class="col-md-3 mb-3">
        <a href="#" class="text-decoration-none">
          <div class="card border-0">
            <img src="assets/images/cat_home.jpg" class="card-img-top" alt="Home & Garden" style="height: 150px; object-fit: cover;">
            <div class="card-body">
              <h5 class="card-title">Home & Garden</h5>
            </div>
          </div>
        </a>
      </div>
      <div class="col-md-3 mb-3">
        <a href="#" class="text-decoration-none">
          <div class="card border-0">
            <img src="assets/images/cat_books.jpg" class="card-img-top" alt="Books" style="height: 150px; object-fit: cover;">
            <div class="card-body">
              <h5 class="card-title">Books</h5>
            </div>
          </div>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- Testimonials -->
<section class="py-5">
  <div class="container">
    <h2 class="text-center mb-4">What Our Customers Say</h2>
    <div class="row">
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <p class="card-text">"Fast shipping and great quality. Will shop here again!"</p>
            <small class="text-muted">— John D.</small>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <p class="card-text">"Amazing deals and excellent customer service."</p>
            <small class="text-muted">— Sarah M.</small>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <p class="card-text">"Easy to navigate and secure checkout process."</p>
            <small class="text-muted">— Mike L.</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>