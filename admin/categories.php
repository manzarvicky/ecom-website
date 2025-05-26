<?php
session_start();
include '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header('Location: ../login.php');
    exit();
}

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = mysqli_real_escape_string($conn, $_POST['name']);
                $description = mysqli_real_escape_string($conn, $_POST['description']);
                
                $query = "INSERT INTO categories (name, description) VALUES ('$name', '$description')";
                mysqli_query($conn, $query);
                break;

            case 'edit':
                $id = (int)$_POST['id'];
                $name = mysqli_real_escape_string($conn, $_POST['name']);
                $description = mysqli_real_escape_string($conn, $_POST['description']);

                $query = "UPDATE categories SET name = '$name', description = '$description' WHERE id = $id";
                mysqli_query($conn, $query);
                break;

            case 'delete':
                $id = (int)$_POST['id'];
                // First check if category has products
                $check_query = "SELECT COUNT(*) as count FROM products WHERE category_id = $id";
                $result = mysqli_query($conn, $check_query);
                $count = mysqli_fetch_assoc($result)['count'];

                if ($count > 0) {
                    $_SESSION['error'] = "Cannot delete category. It has $count products associated with it.";
                } else {
                    $query = "DELETE FROM categories WHERE id = $id";
                    mysqli_query($conn, $query);
                }
                break;
        }

        header('Location: categories.php');
        exit();
    }
}

// Get all categories with product counts
$query = "SELECT c.*, COUNT(p.id) as product_count 
         FROM categories c 
         LEFT JOIN products p ON c.id = p.category_id 
         GROUP BY c.id 
         ORDER BY c.name ASC";
$categories = mysqli_query($conn, $query);

include 'header.php';
?>

<div class="admin-categories">
    <div class="page-header">
        <h1>Manage Categories</h1>
        <button class="btn-add" onclick="showAddModal()">
            <i class="fas fa-plus"></i> Add Category
        </button>
    </div>

    <?php if (isset($_SESSION['error'])) { ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php } ?>

    <div class="categories-grid">
        <?php while($category = mysqli_fetch_assoc($categories)) { ?>
            <div class="category-card">
                <div class="category-info">
                    <h3><?php echo $category['name']; ?></h3>
                    <p class="description"><?php echo $category['description']; ?></p>
                    <span class="product-count">
                        <?php echo $category['product_count']; ?> Products
                    </span>
                </div>
                <div class="category-actions">
                    <button class="btn-edit" onclick="showEditModal(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn-delete" onclick="confirmDelete(<?php echo $category['id']; ?>)">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<!-- Add Category Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('addModal')">&times;</span>
        <h2>Add New Category</h2>
        <form action="categories.php" method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="name">Category Name</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required></textarea>
            </div>

            <button type="submit" class="btn-submit">Add Category</button>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editModal')">&times;</span>
        <h2>Edit Category</h2>
        <form action="categories.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label for="edit_name">Category Name</label>
                <input type="text" id="edit_name" name="name" required>
            </div>

            <div class="form-group">
                <label for="edit_description">Description</label>
                <textarea id="edit_description" name="description" required></textarea>
            </div>

            <button type="submit" class="btn-submit">Update Category</button>
        </form>
    </div>
</div>

<style>
.admin-categories {
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

.alert {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 4px;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.category-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    padding: 1.5rem;
}

.category-info h3 {
    margin: 0 0 1rem 0;
    color: #333;
}

.description {
    color: #666;
    margin-bottom: 1rem;
    line-height: 1.5;
}

.product-count {
    display: inline-block;
    background: #e9ecef;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.9rem;
    color: #495057;
    margin-bottom: 1rem;
}

.category-actions {
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
    max-width: 500px;
    margin: 2rem auto;
    padding: 2rem;
    border-radius: 8px;
    position: relative;
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

label {
    display: block;
    margin-bottom: 0.5rem;
    color: #333;
}

input[type="text"],
textarea {
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
    .categories-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function showAddModal() {
    document.getElementById('addModal').style.display = 'block';
}

function showEditModal(category) {
    document.getElementById('edit_id').value = category.id;
    document.getElementById('edit_name').value = category.name;
    document.getElementById('edit_description').value = category.description;

    document.getElementById('editModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function confirmDelete(categoryId) {
    if (confirm('Are you sure you want to delete this category?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'categories.php';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = categoryId;

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