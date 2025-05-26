<?php
session_start();
include 'config/database.php';

if (!isset($_GET['id'])) {
    header('Location: products.php');
    exit();
}

$product_id = (int)$_GET['id'];

// Get product details
$query = "SELECT p.*, c.name as category_name, c.slug as category_slug 
         FROM products p 
         LEFT JOIN categories c ON p.category_id = c.id 
         WHERE p.id = $product_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    header('Location: products.php');
    exit();
}

$product = mysqli_fetch_assoc($result);

// Get related products
$category_id = $product['category_id'];
$related_query = "SELECT * FROM products 
                 WHERE category_id = $category_id 
                 AND id != $product_id 
                 LIMIT 4";
$related_products = mysqli_query($conn, $related_query);

// Get product reviews
$reviews_query = "SELECT r.*, u.name as user_name 
                FROM reviews r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.product_id = $product_id 
                ORDER BY r.created_at DESC";
$reviews = mysqli_query($conn, $reviews_query);

// Calculate average rating
$rating_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                FROM reviews 
                WHERE product_id = $product_id";
$rating_result = mysqli_query($conn, $rating_query);
$rating_data = mysqli_fetch_assoc($rating_result);
$avg_rating = round($rating_data['avg_rating'], 1);
$total_reviews = $rating_data['total_reviews'];

include 'includes/header.php';
?>

<div class="product-container">
    <div class="container">
        <div class="product-details">
            <div class="product-gallery">
                <div class="main-image">
                    <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>">
                </div>
                <!-- Add thumbnail images here if available -->
            </div>

            <div class="product-info">
                <nav class="product-breadcrumb">
                    <a href="index.php">Home</a> /
                    <a href="products.php?category=<?php echo $product['category_slug']; ?>">
                        <?php echo $product['category_name']; ?>
                    </a> /
                    <span><?php echo $product['name']; ?></span>
                </nav>

                <h1><?php echo $product['name']; ?></h1>

                <div class="product-meta">
                    <div class="rating">
                        <?php
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $avg_rating) {
                                echo '<i class="fas fa-star"></i>';
                            } else if ($i - 0.5 <= $avg_rating) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                        <span class="rating-count">(<?php echo $total_reviews; ?> reviews)</span>
                    </div>
                    <div class="stock-status <?php echo $product['stock'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                        <?php echo $product['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                    </div>
                </div>

                <div class="price">$<?php echo number_format($product['price'], 2); ?></div>

                <div class="description">
                    <?php echo nl2br($product['description']); ?>
                </div>

                <?php if($product['stock'] > 0) { ?>
                    <form action="cart.php" method="POST" class="add-to-cart-form">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                        <div class="quantity-control">
                            <button type="button" onclick="updateQuantity(-1)" class="quantity-btn">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" id="quantity-input">
                            <button type="button" onclick="updateQuantity(1)" class="quantity-btn">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <button type="submit" class="add-to-cart-btn">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </form>
                <?php } ?>
            </div>
        </div>

        <div class="product-tabs">
            <div class="tab-buttons">
                <button class="tab-btn active" onclick="showTab('description')">Description</button>
                <button class="tab-btn" onclick="showTab('reviews')">Reviews (<?php echo $total_reviews; ?>)</button>
            </div>

            <div class="tab-content">
                <div id="description" class="tab-pane active">
                    <?php echo nl2br($product['description']); ?>
                </div>

                <div id="reviews" class="tab-pane">
                    <div class="reviews-container">
                        <?php if(mysqli_num_rows($reviews) > 0) { 
                            while($review = mysqli_fetch_assoc($reviews)) { ?>
                                <div class="review">
                                    <div class="review-header">
                                        <div class="reviewer"><?php echo $review['user_name']; ?></div>
                                        <div class="review-rating">
                                            <?php
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo $i <= $review['rating'] ? 
                                                    '<i class="fas fa-star"></i>' : 
                                                    '<i class="far fa-star"></i>';
                                            }
                                            ?>
                                        </div>
                                        <div class="review-date">
                                            <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                        </div>
                                    </div>
                                    <div class="review-content">
                                        <?php echo nl2br($review['comment']); ?>
                                    </div>
                                </div>
                            <?php }
                        } else { ?>
                            <p class="no-reviews">No reviews yet. Be the first to review this product!</p>
                        <?php } ?>
                    </div>

                    <?php if(isset($_SESSION['user_id'])) { ?>
                        <div class="write-review">
                            <h3>Write a Review</h3>
                            <form action="submit-review.php" method="POST" class="review-form">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                <div class="rating-select">
                                    <label>Your Rating:</label>
                                    <div class="star-rating">
                                        <?php for($i = 5; $i >= 1; $i--) { ?>
                                            <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>">
                                            <label for="star<?php echo $i; ?>"><i class="far fa-star"></i></label>
                                        <?php } ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="comment">Your Review:</label>
                                    <textarea name="comment" id="comment" required></textarea>
                                </div>
                                <button type="submit" class="submit-review-btn">Submit Review</button>
                            </form>
                        </div>
                    <?php } else { ?>
                        <p class="login-to-review">
                            Please <a href="login.php">login</a> to write a review.
                        </p>
                    <?php } ?>
                </div>
            </div>
        </div>

        <?php if(mysqli_num_rows($related_products) > 0) { ?>
            <div class="related-products">
                <h2>Related Products</h2>
                <div class="product-grid">
                    <?php while($related = mysqli_fetch_assoc($related_products)) { ?>
                        <div class="product-card">
                            <img src="<?php echo $related['image_url']; ?>" alt="<?php echo $related['name']; ?>">
                            <h3><?php echo $related['name']; ?></h3>
                            <p class="price">$<?php echo number_format($related['price'], 2); ?></p>
                            <a href="product.php?id=<?php echo $related['id']; ?>" class="btn">View Details</a>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<style>
.product-container {
    padding: 2rem 0;
}

.product-details {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
    margin-bottom: 3rem;
}

.product-gallery {
    background: #fff;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.main-image img {
    width: 100%;
    height: auto;
    border-radius: 4px;
}

.product-breadcrumb {
    margin-bottom: 1rem;
    color: #666;
}

.product-breadcrumb a {
    color: #666;
    text-decoration: none;
}

.product-breadcrumb a:hover {
    color: #007bff;
}

.product-info h1 {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.product-meta {
    display: flex;
    align-items: center;
    gap: 2rem;
    margin-bottom: 1rem;
}

.rating {
    color: #ffc107;
}

.rating-count {
    color: #666;
    margin-left: 0.5rem;
}

.stock-status {
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.9rem;
}

.in-stock {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.out-of-stock {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.price {
    font-size: 2rem;
    color: #007bff;
    font-weight: bold;
    margin-bottom: 1rem;
}

.description {
    color: #666;
    line-height: 1.6;
    margin-bottom: 2rem;
}

.quantity-control {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.quantity-btn {
    background: #f8f9fa;
    border: 1px solid #ddd;
    padding: 0.5rem;
    cursor: pointer;
    border-radius: 4px;
}

#quantity-input {
    width: 60px;
    text-align: center;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.add-to-cart-btn {
    width: 100%;
    padding: 1rem;
    background: #28a745;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.3s;
}

.add-to-cart-btn:hover {
    background: #218838;
}

.product-tabs {
    margin-bottom: 3rem;
}

.tab-buttons {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.tab-btn {
    padding: 0.8rem 1.5rem;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    font-size: 1rem;
    color: #666;
}

.tab-btn.active {
    color: #007bff;
    border-bottom-color: #007bff;
}

.tab-pane {
    display: none;
    background: #fff;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.tab-pane.active {
    display: block;
}

.review {
    border-bottom: 1px solid #eee;
    padding: 1rem 0;
}

.review:last-child {
    border-bottom: none;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.reviewer {
    font-weight: bold;
}

.review-rating {
    color: #ffc107;
}

.review-date {
    color: #666;
    font-size: 0.9rem;
}

.review-content {
    color: #333;
    line-height: 1.6;
}

.write-review {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #eee;
}

.review-form .form-group {
    margin-bottom: 1rem;
}

.review-form label {
    display: block;
    margin-bottom: 0.5rem;
}

.review-form textarea {
    width: 100%;
    height: 100px;
    padding: 0.8rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    resize: vertical;
}

.star-rating {
    display: flex;
    flex-direction: row-reverse;
    gap: 0.5rem;
}

.star-rating input {
    display: none;
}

.star-rating label {
    cursor: pointer;
    color: #ddd;
    font-size: 1.5rem;
}

.star-rating input:checked ~ label,
.star-rating label:hover,
.star-rating label:hover ~ label {
    color: #ffc107;
}

.submit-review-btn {
    background: #007bff;
    color: #fff;
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.3s;
}

.submit-review-btn:hover {
    background: #0056b3;
}

.login-to-review {
    text-align: center;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 4px;
}

.related-products h2 {
    margin-bottom: 2rem;
}

@media (max-width: 768px) {
    .product-details {
        grid-template-columns: 1fr;
    }

    .product-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .review-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>

<script>
function updateQuantity(change) {
    const input = document.getElementById('quantity-input');
    const currentValue = parseInt(input.value);
    const maxStock = <?php echo $product['stock']; ?>;
    const newValue = currentValue + change;

    if (newValue >= 1 && newValue <= maxStock) {
        input.value = newValue;
    }
}

function showTab(tabId) {
    // Hide all tab panes
    document.querySelectorAll('.tab-pane').forEach(pane => {
        pane.classList.remove('active');
    });

    // Deactivate all tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    // Show selected tab pane
    document.getElementById(tabId).classList.add('active');

    // Activate selected tab button
    event.target.classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?>