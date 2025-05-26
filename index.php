<?php
session_start();
include 'config/database.php';
include 'includes/header.php';

// Fetch featured products
$query = "SELECT * FROM products WHERE featured = 1 LIMIT 6";
$result = mysqli_query($conn, $query);
?>

<div class="hero-section">
    <div class="container">
        <h1>Welcome to Our Store</h1>
        <p>Discover amazing products at great prices</p>
    </div>
</div>

<div class="featured-products">
    <div class="container">
        <h2>Featured Products</h2>
        <div class="product-grid">
            <?php while($product = mysqli_fetch_assoc($result)) { ?>
                <div class="product-card">
                    <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>">
                    <h3><?php echo $product['name']; ?></h3>
                    <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn">View Details</a>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>