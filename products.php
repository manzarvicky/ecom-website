<?php
session_start();
include 'config/database.php';

// Get filters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;

// Build query
$query = "SELECT p.*, c.name as category_name 
         FROM products p 
         LEFT JOIN categories c ON p.category_id = c.id 
         WHERE 1=1";

if ($category) {
    $query .= " AND c.slug = '$category'";
}

if ($search) {
    $search = mysqli_real_escape_string($conn, $search);
    $query .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%')";
}

// Add sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY p.created_at ASC";
        break;
    default:
        $query .= " ORDER BY p.created_at DESC";
}

// Get total products for pagination
$total_result = mysqli_query($conn, $query);
$total_products = mysqli_num_rows($total_result);
$total_pages = ceil($total_products / $per_page);

// Add pagination
$offset = ($page - 1) * $per_page;
$query .= " LIMIT $offset, $per_page";

// Get products
$products = mysqli_query($conn, $query);

// Get categories for filter
$categories_query = "SELECT * FROM categories";
$categories = mysqli_query($conn, $categories_query);

include 'includes/header.php';
?>

<div class="products-container">
    <div class="container">
        <div class="products-header">
            <h1>Our Products</h1>
            <div class="filters">
                <form action="products.php" method="GET" class="filter-form">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </div>
                    
                    <select name="category" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php while($cat = mysqli_fetch_assoc($categories)) { ?>
                            <option value="<?php echo $cat['slug']; ?>" <?php echo $category == $cat['slug'] ? 'selected' : ''; ?>>
                                <?php echo $cat['name']; ?>
                            </option>
                        <?php } ?>
                    </select>
                    
                    <select name="sort" onchange="this.form.submit()">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="product-grid">
            <?php if(mysqli_num_rows($products) > 0) { 
                while($product = mysqli_fetch_assoc($products)) { ?>
                    <div class="product-card">
                        <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>">
                        <div class="product-info">
                            <h3><?php echo $product['name']; ?></h3>
                            <span class="category"><?php echo $product['category_name']; ?></span>
                            <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
                            <div class="product-actions">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-view">View Details</a>
                                <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn btn-cart">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php } 
            } else { ?>
                <div class="no-products">
                    <i class="fas fa-box-open"></i>
                    <p>No products found</p>
                </div>
            <?php } ?>
        </div>

        <?php if($total_pages > 1) { ?>
            <div class="pagination">
                <?php if($page > 1) { ?>
                    <a href="?page=<?php echo $page-1; ?>&category=<?php echo $category; ?>&sort=<?php echo $sort; ?>&search=<?php echo $search; ?>" class="page-link">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php } ?>
                
                <?php for($i = 1; $i <= $total_pages; $i++) { ?>
                    <a href="?page=<?php echo $i; ?>&category=<?php echo $category; ?>&sort=<?php echo $sort; ?>&search=<?php echo $search; ?>" 
                       class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php } ?>
                
                <?php if($page < $total_pages) { ?>
                    <a href="?page=<?php echo $page+1; ?>&category=<?php echo $category; ?>&sort=<?php echo $sort; ?>&search=<?php echo $search; ?>" class="page-link">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>

<style>
.products-container {
    padding: 2rem 0;
}

.products-header {
    margin-bottom: 2rem;
}

.products-header h1 {
    margin-bottom: 1rem;
}

.filters {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.filter-form {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.search-box {
    display: flex;
    align-items: center;
    flex: 1;
    min-width: 200px;
}

.search-box input {
    flex: 1;
    padding: 0.8rem;
    border: 1px solid #ddd;
    border-radius: 4px 0 0 4px;
    font-size: 1rem;
}

.search-box button {
    padding: 0.8rem 1rem;
    background: #007bff;
    color: #fff;
    border: none;
    border-radius: 0 4px 4px 0;
    cursor: pointer;
}

select {
    padding: 0.8rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    min-width: 150px;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.product-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.3s;
}

.product-card:hover {
    transform: translateY(-5px);
}

.product-card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.product-info {
    padding: 1rem;
}

.product-info h3 {
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
}

.category {
    color: #666;
    font-size: 0.9rem;
    display: block;
    margin-bottom: 0.5rem;
}

.price {
    color: #007bff;
    font-weight: bold;
    font-size: 1.2rem;
    margin-bottom: 1rem;
}

.product-actions {
    display: flex;
    gap: 0.5rem;
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    text-decoration: none;
    text-align: center;
    font-size: 0.9rem;
    flex: 1;
}

.btn-view {
    background: #007bff;
    color: #fff;
}

.btn-cart {
    background: #28a745;
    color: #fff;
    border: none;
    cursor: pointer;
}

.no-products {
    grid-column: 1 / -1;
    text-align: center;
    padding: 3rem;
    color: #666;
}

.no-products i {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.page-link {
    padding: 0.5rem 1rem;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    color: #333;
    text-decoration: none;
}

.page-link.active {
    background: #007bff;
    color: #fff;
    border-color: #007bff;
}

@media (max-width: 768px) {
    .filters {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-form {
        flex-direction: column;
    }

    .search-box {
        width: 100%;
    }

    select {
        width: 100%;
    }
}
</style>

<?php include 'includes/footer.php'; ?>