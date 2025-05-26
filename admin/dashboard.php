<?php
session_start();
include '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch statistics
$stats = array();

// Total Orders
$query = "SELECT COUNT(*) as total FROM orders";
$result = mysqli_query($conn, $query);
$stats['orders'] = mysqli_fetch_assoc($result)['total'];

// Total Products
$query = "SELECT COUNT(*) as total FROM products";
$result = mysqli_query($conn, $query);
$stats['products'] = mysqli_fetch_assoc($result)['total'];

// Total Users
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";
$result = mysqli_query($conn, $query);
$stats['users'] = mysqli_fetch_assoc($result)['total'];

// Total Revenue
$query = "SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'";
$result = mysqli_query($conn, $query);
$stats['revenue'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Recent Orders
$query = "SELECT o.*, u.name as customer_name 
         FROM orders o 
         JOIN users u ON o.user_id = u.id 
         ORDER BY o.created_at DESC LIMIT 5";
$recent_orders = mysqli_query($conn, $query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - E-Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <nav class="admin-nav">
            <div class="admin-nav-header">
                <h2>E-Store Admin</h2>
                <span class="admin-user"><?php echo $_SESSION['user_name']; ?></span>
            </div>
            <ul class="admin-menu">
                <li class="active"><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <main class="admin-main">
            <h1>Dashboard</h1>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Total Orders</h3>
                    <p><?php echo $stats['orders']; ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-box"></i>
                    <h3>Total Products</h3>
                    <p><?php echo $stats['products']; ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h3>Total Users</h3>
                    <p><?php echo $stats['users']; ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-dollar-sign"></i>
                    <h3>Total Revenue</h3>
                    <p>$<?php echo number_format($stats['revenue'], 2); ?></p>
                </div>
            </div>

            <div class="recent-orders">
                <h2>Recent Orders</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($order = mysqli_fetch_assoc($recent_orders)) { ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo $order['customer_name']; ?></td>
                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td><span class="status-<?php echo strtolower($order['status']); ?>"><?php echo $order['status']; ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn-view">View</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script src="js/admin.js"></script>
</body>
</html>