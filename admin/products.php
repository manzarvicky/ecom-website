<?php
session_start();
include '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header('Location: ../login.php');
    exit();
}

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = mysqli_real_escape_string($conn, $_POST['name']);
                $description = mysqli_real_escape_string($conn, $_POST['description']);
                $price = (float)$_POST['price'];
                $stock = (int)$_POST['stock'];
                $category_id = (int)$_POST['category_id'];
                $featured = isset($_POST['featured']) ? 1 : 0;
                
                // Handle image upload
                $image_url = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $target_dir = "../assets/images/products/";
                    $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
                    $file_name = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                        $image_url = 'assets/images/products/' . $file_name;
                    }
                }

                $query = "INSERT INTO products (name, description, price, stock, category_id, image_url, featured) 
                          VALUES ('$name', '$description', $price, $stock, $category_id, '$image_url', $featured)";
                mysqli_query($conn, $query);
                break;

            case 'edit':
                $id = (int)$_POST['id'];
                $name = mysqli_real_escape_string($conn, $_POST['name']);
                $description = mysqli_real_escape_string($conn, $_POST['description']);
                $price = (float)$_POST['price'];
                $stock = (int)$_POST['stock'];
                $category_id = (int)$_POST['category_id'];
                $featured = isset($_POST['featured']) ? 1 : 0;

                $query = "UPDATE products 
                          SET name = '$name', description = '$description', 
                              price = $price, stock = $stock, 
                              category_id = $category_id, featured = $featured";

                // Handle image upload for edit
                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $target_dir = "../assets/images/products/";
                    $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
                    $file_name = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                        $image_url = 'assets/images/products/' . $file_name;
                        $query .= ", image_url = '$image_url'";
                    }
                }

                $query .= " WHERE id = $id";
                mysqli_query($conn, $query);
                break;

            case 'delete':
                $id = (int)$_POST['id'];
                $query = "DELETE FROM products WHERE id = $id";
                mysqli_query($conn, $query);
                break;
        }

        header('Location: products.php');
        exit();
    }
}

// Get all products with category names
$query = "SELECT p.*, c.name as category_name 
         FROM products p 
         LEFT JOIN categories c ON p.category_id = c.id 
         ORDER BY p.created_at DESC";
$products = mysqli_query($conn, $query);

// Get categories for form
$categories_query = "SELECT * FROM categories";
$categories = mysqli_query($conn, $categories_query);

include 'header.php';
?>

<div class="admin-products">
    <div class="page-header">
        <h1>Manage Products</h1>
        <button class="btn-add" onclick="showAddModal()">
            <i class="fas fa-plus"></i> Add Product
        </button>
    </div>

    <div class="products-grid">
        <?php while($product = mysqli_fetch_assoc($products)) { ?>
            <div class="product-card">
                <div class="product-image">
                    <img src="../<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>">
                    <?php if($product['featured']) { ?>
                        <span class="featured-badge">Featured</span>
                    <?php } ?>
                </div>
                <div class="product-info">
                    <h3><?php echo $product['name']; ?></h3>
                    <span class="category"><?php echo $product['category_name']; ?></span>
                    <div class="price">$<?php echo number_format($product['price'], 2); ?></div>
                    <div class="stock">Stock: <?php echo $product['stock']; ?></div>
                </div>
                <div class="product-actions">
                    <button class="btn-edit" onclick="showEditModal(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn-delete" onclick="confirmDelete(<?php echo $product['id']; ?>)">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<!-- Add Product Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('addModal')">&times;</span>
        <h2>Add New Product</h2>
        <form action="products.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="price">Price</label>
                    <input type="number" id="price" name="price" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="stock">Stock</label>
                    <input type="number" id="stock" name="stock" required>
                </div>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category_id" required>
                    <?php 
                    mysqli_data_seek($categories, 0);
                    while($category = mysqli_fetch_assoc($categories)) { ?>
                        <option value="<?php echo $category['id']; ?>">
                            <?php echo $category['name']; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="form-group">
                <label for="image">Product Image</label>
                <input type="file" id="image" name="image" accept="image/*" required>
            </div>

            <div class="form-group checkbox">
                <input type="checkbox" id="featured" name="featured">
                <label for="featured">Featured Product</label>
            </div>

            <button type="submit" class="btn-submit">Add Product</button>
        </form>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editModal')">&times;</span>
        <h2>Edit Product</h2>
        <form action="products.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label for="edit_name">Product Name</label>
                <input type="text" id="edit_name" name="name" required>
            </div>

            <div class="form-group">
                <label for="edit_description">Description</label>
                <textarea id="edit_description" name="description" required></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="edit_price">Price</label>
                    <input type="number" id="edit_price" name="price" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="edit_stock">Stock</label>
                    <input type="number" id="edit_stock" name="stock" required>
                </div>
            </div>

            <div class="form-group">
                <label for="edit_category">Category</label>
                <select id="edit_category" name="category_id" required>
                    <?php 
                    mysqli_data_seek($categories, 0);
                    while($category = mysqli_fetch_assoc($categories)) { ?>
                        <option value="<?php echo $category['id']; ?>">
                            <?php echo $category['name']; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="form-group">
                <label for="edit_image">Product Image</label>
                <input type="file" id="edit_image" name="image" accept="image/*">
                <small>Leave empty to keep current image</small>
            </div>

            <div class="form-group checkbox">
                <input type="checkbox" id="edit_featured" name="featured">
                <label for="edit_featured">Featured Product</label>
            </div>

            <button type="submit" class="btn-submit">Update Product</button>
        </form>
    </div>
</div>

<style>
.admin-products {
    padding: 2rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.btn-add {
    background: #28a745;
    color: #fff;
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.product-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    overflow: hidden;
}

.product-image {
    position: relative;
    height: 200px;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.featured-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: #ffc107;
    color: #000;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
}

.product-info {
    padding: 1rem;
}

.product-info h3 {
    margin-bottom: 0.5rem;
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
    margin-bottom: 0.5rem;
}

.stock {
    color: #666;
    font-size: 0.9rem;
}

.product-actions {
    padding: 1rem;
    display: flex;
    gap: 0.5rem;
}

.btn-edit,
.btn-delete {
    flex: 1;
    padding: 0.5rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-edit {
    background: #007bff;
    color: #fff;
}

.btn-delete {
    background: #dc3545;
    color: #fff;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
}

.modal-content {
    background: #fff;
    width: 90%;
    max-width: 600px;
    margin: 2rem auto;
    padding: 2rem;
    border-radius: 8px;
    position: relative;
    max-height: 90vh;
    overflow-y: auto;
}

.close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 1.5rem;
    cursor: pointer;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

label {
    display: block;
    margin-bottom: 0.5rem;
}

input[type="text"],
input[type="number"],
textarea,
select {
    width: 100%;
    padding: 0.8rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
}

textarea {
    height: 100px;
    resize: vertical;
}

.checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.checkbox input {
    width: auto;
}

.checkbox label {
    margin: 0;
}

.btn-submit {
    background: #28a745;
    color: #fff;
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    width: 100%;
}

@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: 1fr;
    }

    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function showAddModal() {
    document.getElementById('addModal').style.display = 'block';
}

function showEditModal(product) {
    document.getElementById('edit_id').value = product.id;
    document.getElementById('edit_name').value = product.name;
    document.getElementById('edit_description').value = product.description;
    document.getElementById('edit_price').value = product.price;
    document.getElementById('edit_stock').value = product.stock;
    document.getElementById('edit_category').value = product.category_id;
    document.getElementById('edit_featured').checked = product.featured == 1;

    document.getElementById('editModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function confirmDelete(productId) {
    if (confirm('Are you sure you want to delete this product?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'products.php';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = productId;

        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = 'none';
    }
}
</script>

<?php include 'footer.php'; ?>