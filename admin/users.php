<?php
session_start();
include '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header('Location: ../login.php');
    exit();
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_role':
                $user_id = (int)$_POST['user_id'];
                $role = mysqli_real_escape_string($conn, $_POST['role']);
                
                $query = "UPDATE users SET role = '$role' WHERE id = $user_id";
                mysqli_query($conn, $query);
                break;

            case 'toggle_status':
                $user_id = (int)$_POST['user_id'];
                $status = (int)$_POST['status'];
                
                $query = "UPDATE users SET is_active = $status WHERE id = $user_id";
                mysqli_query($conn, $query);
                break;

            case 'delete':
                $user_id = (int)$_POST['user_id'];
                // Check if user has any orders
                $check_query = "SELECT COUNT(*) as count FROM orders WHERE user_id = $user_id";
                $result = mysqli_query($conn, $check_query);
                $count = mysqli_fetch_assoc($result)['count'];

                if ($count > 0) {
                    $_SESSION['error'] = "Cannot delete user. They have $count orders associated with their account.";
                } else {
                    $query = "DELETE FROM users WHERE id = $user_id";
                    mysqli_query($conn, $query);
                }
                break;
        }

        header('Location: users.php');
        exit();
    }
}

// Get all users with their order counts
$query = "SELECT u.*, COUNT(o.id) as order_count, 
                 SUM(CASE WHEN o.status = 'delivered' THEN oi.quantity * oi.price ELSE 0 END) as total_spent
         FROM users u
         LEFT JOIN orders o ON u.id = o.user_id
         LEFT JOIN order_items oi ON o.id = oi.order_id
         GROUP BY u.id
         ORDER BY u.created_at DESC";
$users = mysqli_query($conn, $query);

include 'header.php';
?>

<div class="admin-users">
    <div class="page-header">
        <h1>Manage Users</h1>
    </div>

    <?php if (isset($_SESSION['error'])) { ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php } ?>

    <div class="users-container">
        <div class="filters">
            <button class="filter-btn active" data-role="all">All Users</button>
            <button class="filter-btn" data-role="admin">Admins</button>
            <button class="filter-btn" data-role="user">Customers</button>
            <button class="filter-btn" data-status="1">Active</button>
            <button class="filter-btn" data-status="0">Inactive</button>
        </div>

        <div class="users-table-wrapper">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Joined Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($user = mysqli_fetch_assoc($users)) { ?>
                        <tr class="user-row" data-role="<?php echo $user['role']; ?>" data-status="<?php echo $user['is_active']; ?>">
                            <td><?php echo $user['id']; ?></td>
                            <td>
                                <div class="user-info">
                                    <span class="user-name"><?php echo $user['name']; ?></span>
                                    <?php if($user['role'] == 'admin') { ?>
                                        <span class="admin-badge">Admin</span>
                                    <?php } ?>
                                </div>
                            </td>
                            <td><?php echo $user['email']; ?></td>
                            <td>
                                <form action="users.php" method="POST" class="role-form">
                                    <input type="hidden" name="action" value="update_role">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <select name="role" onchange="this.form.submit()" <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                        <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>Customer</option>
                                        <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <form action="users.php" method="POST" class="status-form">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo $user['is_active'] ? '0' : '1'; ?>">
                                    <button type="submit" class="status-toggle <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>" <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <a href="orders.php?user_id=<?php echo $user['id']; ?>" class="order-count">
                                    <?php echo $user['order_count']; ?> orders
                                </a>
                            </td>
                            <td>
                                $<?php echo number_format($user['total_spent'], 2); ?>
                            </td>
                            <td>
                                <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                            </td>
                            <td>
                                <?php if($user['id'] != $_SESSION['user_id']) { ?>
                                    <button class="btn-delete" onclick="confirmDelete(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.admin-users {
    padding: 2rem;
}

.page-header {
    margin-bottom: 2rem;
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

.filters {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 0.5rem 1rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-btn.active {
    background: #007bff;
    color: #fff;
    border-color: #007bff;
}

.users-table-wrapper {
    overflow-x: auto;
}

.users-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.users-table th,
.users-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.users-table th {
    background: #f8f9fa;
    font-weight: 600;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.admin-badge {
    background: #007bff;
    color: #fff;
    padding: 0.2rem 0.5rem;
    border-radius: 20px;
    font-size: 0.8rem;
}

.role-form select,
.status-toggle {
    padding: 0.3rem 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
    cursor: pointer;
}

.status-toggle {
    width: 80px;
    text-align: center;
    border: none;
}

.status-toggle.active {
    background: #28a745;
    color: #fff;
}

.status-toggle.inactive {
    background: #dc3545;
    color: #fff;
}

.order-count {
    color: #007bff;
    text-decoration: none;
}

.order-count:hover {
    text-decoration: underline;
}

.btn-delete {
    background: #dc3545;
    color: #fff;
    border: none;
    border-radius: 4px;
    padding: 0.5rem;
    cursor: pointer;
    transition: background 0.3s ease;
}

.btn-delete:hover {
    background: #c82333;
}

@media (max-width: 1200px) {
    .users-table {
        font-size: 0.9rem;
    }

    .users-table th,
    .users-table td {
        padding: 0.8rem;
    }
}

@media (max-width: 768px) {
    .filters {
        flex-direction: column;
    }

    .filter-btn {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const userRows = document.querySelectorAll('.user-row');

    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            // Filter users
            const role = button.dataset.role;
            const status = button.dataset.status;

            userRows.forEach(row => {
                if (role === 'all' || row.dataset.role === role || 
                    (status !== undefined && row.dataset.status === status)) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
});

function confirmDelete(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'users.php';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';

        const userIdInput = document.createElement('input');
        userIdInput.type = 'hidden';
        userIdInput.name = 'user_id';
        userIdInput.value = userId;

        form.appendChild(actionInput);
        form.appendChild(userIdInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'footer.php'; ?>