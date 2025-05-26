<?php
session_start();
include 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';

// Get user information
$query = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

// Get user orders
$orders_query = "SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC";
$orders = mysqli_query($conn, $orders_query);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    $error = '';
    $success = '';

    // Verify current password
    if (!empty($current_password)) {
        if (password_verify($current_password, $user['password'])) {
            if (!empty($new_password)) {
                if (strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE users 
                                    SET name = '$name', email = '$email', password = '$hashed_password' 
                                    WHERE id = $user_id";
                } else {
                    $error = 'New password must be at least 6 characters long';
                }
            } else {
                $update_query = "UPDATE users 
                                SET name = '$name', email = '$email' 
                                WHERE id = $user_id";
            }

            if (empty($error) && mysqli_query($conn, $update_query)) {
                $success = 'Profile updated successfully';
                $_SESSION['user_name'] = $name;
            } else {
                $error = 'Error updating profile';
            }
        } else {
            $error = 'Current password is incorrect';
        }
    } else {
        $update_query = "UPDATE users 
                        SET name = '$name', email = '$email' 
                        WHERE id = $user_id";
        
        if (mysqli_query($conn, $update_query)) {
            $success = 'Profile updated successfully';
            $_SESSION['user_name'] = $name;
        } else {
            $error = 'Error updating profile';
        }
    }
}

include 'includes/header.php';
?>

<div class="account-container">
    <div class="container">
        <div class="account-content">
            <div class="account-sidebar">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3><?php echo $user['name']; ?></h3>
                </div>
                <ul class="account-nav">
                    <li>
                        <a href="?tab=profile" class="<?php echo $active_tab == 'profile' ? 'active' : ''; ?>">
                            <i class="fas fa-user-circle"></i> Profile
                        </a>
                    </li>
                    <li>
                        <a href="?tab=orders" class="<?php echo $active_tab == 'orders' ? 'active' : ''; ?>">
                            <i class="fas fa-shopping-bag"></i> Orders
                        </a>
                    </li>
                    <li>
                        <a href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <div class="account-main">
                <?php if ($active_tab == 'profile') { ?>
                    <div class="account-section">
                        <h2>Profile Settings</h2>
                        
                        <?php if(isset($error) && $error) { ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php } ?>
                        <?php if(isset($success) && $success) { ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php } ?>

                        <form method="POST" action="account.php?tab=profile" class="profile-form">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?php echo $user['name']; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password">
                                <small>Leave blank if you don't want to change password</small>
                            </div>

                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password">
                                <small>Minimum 6 characters</small>
                            </div>

                            <button type="submit" name="update_profile" class="btn-update">
                                Update Profile
                            </button>
                        </form>
                    </div>
                <?php } else if ($active_tab == 'orders') { ?>
                    <div class="account-section">
                        <h2>My Orders</h2>

                        <?php if(mysqli_num_rows($orders) > 0) { ?>
                            <div class="orders-list">
                                <?php while($order = mysqli_fetch_assoc($orders)) { ?>
                                    <div class="order-card">
                                        <div class="order-header">
                                            <div class="order-info">
                                                <span class="order-number">#<?php echo $order['id']; ?></span>
                                                <span class="order-date">
                                                    <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                                </span>
                                            </div>
                                            <div class="order-status status-<?php echo strtolower($order['status']); ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </div>
                                        </div>

                                        <?php
                                        // Get order items
                                        $items_query = "SELECT oi.*, p.name, p.image_url 
                                                       FROM order_items oi 
                                                       JOIN products p ON oi.product_id = p.id 
                                                       WHERE oi.order_id = {$order['id']}";
                                        $items = mysqli_query($conn, $items_query);
                                        ?>

                                        <div class="order-items">
                                            <?php while($item = mysqli_fetch_assoc($items)) { ?>
                                                <div class="order-item">
                                                    <img src="<?php echo $item['image_url']; ?>" alt="<?php echo $item['name']; ?>">
                                                    <div class="item-details">
                                                        <h4><?php echo $item['name']; ?></h4>
                                                        <p>Quantity: <?php echo $item['quantity']; ?></p>
                                                        <p class="item-price">$<?php echo number_format($item['price'], 2); ?></p>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>

                                        <div class="order-footer">
                                            <div class="order-total">
                                                Total: $<?php echo number_format($order['total_amount'], 2); ?>
                                            </div>
                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn-view-order">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } else { ?>
                            <div class="no-orders">
                                <i class="fas fa-shopping-bag"></i>
                                <p>You haven't placed any orders yet</p>
                                <a href="products.php" class="btn">Start Shopping</a>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<style>
.account-container {
    padding: 2rem 0;
    background: #f8f9fa;
}

.account-content {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 2rem;
}

.account-sidebar {
    background: #fff;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    height: fit-content;
}

.user-info {
    text-align: center;
    padding-bottom: 1.5rem;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid #eee;
}

.user-avatar {
    width: 80px;
    height: 80px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}

.user-avatar i {
    font-size: 2rem;
    color: #666;
}

.account-nav {
    list-style: none;
}

.account-nav a {
    display: flex;
    align-items: center;
    padding: 0.8rem;
    color: #333;
    text-decoration: none;
    border-radius: 4px;
    transition: background 0.3s;
}

.account-nav a i {
    width: 20px;
    margin-right: 10px;
}

.account-nav a:hover,
.account-nav a.active {
    background: #f8f9fa;
    color: #007bff;
}

.account-main {
    background: #fff;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.account-section h2 {
    margin-bottom: 2rem;
}

.profile-form .form-group {
    margin-bottom: 1.5rem;
}

.profile-form label {
    display: block;
    margin-bottom: 0.5rem;
    color: #333;
}

.profile-form input {
    width: 100%;
    padding: 0.8rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
}

.profile-form small {
    display: block;
    margin-top: 0.25rem;
    color: #666;
    font-size: 0.875rem;
}

.btn-update {
    background: #007bff;
    color: #fff;
    padding: 0.8rem 2rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.3s;
}

.btn-update:hover {
    background: #0056b3;
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

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.orders-list {
    display: grid;
    gap: 1rem;
}

.order-card {
    border: 1px solid #eee;
    border-radius: 8px;
    overflow: hidden;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
}

.order-number {
    font-weight: bold;
    margin-right: 1rem;
}

.order-date {
    color: #666;
}

.order-status {
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.9rem;
}

.status-pending {
    background: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.status-processing {
    background: rgba(0, 123, 255, 0.1);
    color: #007bff;
}

.status-completed {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.status-cancelled {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.order-items {
    padding: 1rem;
}

.order-item {
    display: flex;
    align-items: center;
    padding: 0.5rem 0;
}

.order-item img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
}

.item-details {
    margin-left: 1rem;
}

.item-details h4 {
    margin-bottom: 0.25rem;
}

.item-price {
    color: #007bff;
    font-weight: bold;
}

.order-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border-top: 1px solid #eee;
}

.order-total {
    font-weight: bold;
}

.btn-view-order {
    background: #007bff;
    color: #fff;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    text-decoration: none;
    transition: background 0.3s;
}

.btn-view-order:hover {
    background: #0056b3;
}

.no-orders {
    text-align: center;
    padding: 3rem;
}

.no-orders i {
    font-size: 3rem;
    color: #666;
    margin-bottom: 1rem;
}

.no-orders .btn {
    display: inline-block;
    background: #007bff;
    color: #fff;
    padding: 0.8rem 2rem;
    border-radius: 4px;
    text-decoration: none;
    margin-top: 1rem;
}

@media (max-width: 768px) {
    .account-content {
        grid-template-columns: 1fr;
    }

    .account-sidebar {
        margin-bottom: 1rem;
    }

    .order-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .order-footer {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}
</style>

<?php include 'includes/footer.php'; ?>