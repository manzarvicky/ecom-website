<?php
session_start();
include '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header('Location: ../login.php');
    exit();
}

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $order_id = (int)$_POST['order_id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $query = "UPDATE orders SET status = '$status', updated_at = NOW() WHERE id = $order_id";
    mysqli_query($conn, $query);
    
    header('Location: orders.php');
    exit();
}

// Get all orders with customer details
$query = "SELECT o.*, u.name as customer_name, u.email as customer_email,
                 COUNT(oi.id) as total_items,
                 SUM(oi.quantity * oi.price) as total_amount
         FROM orders o
         LEFT JOIN users u ON o.user_id = u.id
         LEFT JOIN order_items oi ON o.id = oi.order_id
         GROUP BY o.id
         ORDER BY o.created_at DESC";
$orders = mysqli_query($conn, $query);

include 'header.php';
?>

<div class="admin-orders">
    <div class="page-header">
        <h1>Manage Orders</h1>
    </div>

    <div class="orders-container">
        <div class="filters">
            <button class="filter-btn active" data-status="all">All Orders</button>
            <button class="filter-btn" data-status="pending">Pending</button>
            <button class="filter-btn" data-status="processing">Processing</button>
            <button class="filter-btn" data-status="shipped">Shipped</button>
            <button class="filter-btn" data-status="delivered">Delivered</button>
            <button class="filter-btn" data-status="cancelled">Cancelled</button>
        </div>

        <div class="orders-list">
            <?php while($order = mysqli_fetch_assoc($orders)) { ?>
                <div class="order-card" data-status="<?php echo strtolower($order['status']); ?>">
                    <div class="order-header">
                        <div class="order-id">
                            Order #<?php echo $order['id']; ?>
                        </div>
                        <div class="order-date">
                            <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?>
                        </div>
                        <div class="order-status <?php echo strtolower($order['status']); ?>">
                            <?php echo $order['status']; ?>
                        </div>
                    </div>

                    <div class="order-details">
                        <div class="customer-info">
                            <h3>Customer Details</h3>
                            <p><strong>Name:</strong> <?php echo $order['customer_name']; ?></p>
                            <p><strong>Email:</strong> <?php echo $order['customer_email']; ?></p>
                            <p><strong>Phone:</strong> <?php echo $order['phone']; ?></p>
                        </div>

                        <div class="shipping-info">
                            <h3>Shipping Address</h3>
                            <p><?php echo $order['address']; ?></p>
                            <p><?php echo $order['city'] . ', ' . $order['state'] . ' ' . $order['zip_code']; ?></p>
                            <p><?php echo $order['country']; ?></p>
                        </div>

                        <div class="order-summary">
                            <h3>Order Summary</h3>
                            <p><strong>Total Items:</strong> <?php echo $order['total_items']; ?></p>
                            <p><strong>Total Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
                            <p><strong>Payment Method:</strong> <?php echo $order['payment_method']; ?></p>
                        </div>
                    </div>

                    <div class="order-items">
                        <?php
                        $items_query = "SELECT oi.*, p.name as product_name, p.image_url
                                      FROM order_items oi
                                      LEFT JOIN products p ON oi.product_id = p.id
                                      WHERE oi.order_id = {$order['id']}";
                        $items = mysqli_query($conn, $items_query);
                        ?>
                        <h3>Order Items</h3>
                        <div class="items-grid">
                            <?php while($item = mysqli_fetch_assoc($items)) { ?>
                                <div class="item">
                                    <img src="../<?php echo $item['image_url']; ?>" alt="<?php echo $item['product_name']; ?>">
                                    <div class="item-details">
                                        <h4><?php echo $item['product_name']; ?></h4>
                                        <p>Quantity: <?php echo $item['quantity']; ?></p>
                                        <p>Price: $<?php echo number_format($item['price'], 2); ?></p>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="order-actions">
                        <form action="orders.php" method="POST" class="status-form">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <select name="status" onchange="this.form.submit()">
                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </form>
                        <button class="btn-print" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Order
                        </button>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<style>
.admin-orders {
    padding: 2rem;
}

.page-header {
    margin-bottom: 2rem;
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

.order-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border-bottom: 1px solid #ddd;
}

.order-id {
    font-weight: bold;
    color: #007bff;
}

.order-status {
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.9rem;
    text-transform: capitalize;
}

.order-status.pending { background: #ffc107; color: #000; }
.order-status.processing { background: #17a2b8; color: #fff; }
.order-status.shipped { background: #007bff; color: #fff; }
.order-status.delivered { background: #28a745; color: #fff; }
.order-status.cancelled { background: #dc3545; color: #fff; }

.order-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    padding: 1.5rem;
    border-bottom: 1px solid #ddd;
}

.order-details h3 {
    margin: 0 0 1rem 0;
    color: #333;
}

.order-details p {
    margin: 0.5rem 0;
    color: #666;
}

.order-items {
    padding: 1.5rem;
    border-bottom: 1px solid #ddd;
}

.items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 4px;
}

.item img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
}

.item-details h4 {
    margin: 0 0 0.5rem 0;
    font-size: 0.9rem;
}

.item-details p {
    margin: 0.2rem 0;
    font-size: 0.9rem;
    color: #666;
}

.order-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
}

.status-form select {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
}

.btn-print {
    background: #6c757d;
    color: #fff;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .order-details {
        grid-template-columns: 1fr;
    }

    .items-grid {
        grid-template-columns: 1fr;
    }

    .order-actions {
        flex-direction: column;
        gap: 1rem;
    }

    .status-form select {
        width: 100%;
    }
}

@media print {
    .filters,
    .order-actions,
    .admin-sidebar,
    .admin-header {
        display: none !important;
    }

    .order-card {
        break-inside: avoid;
        margin: 0;
        box-shadow: none;
        border: 1px solid #ddd;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const orderCards = document.querySelectorAll('.order-card');

    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            // Filter orders
            const status = button.dataset.status;
            orderCards.forEach(card => {
                if (status === 'all' || card.dataset.status === status) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
});
</script>

<?php include 'footer.php'; ?>