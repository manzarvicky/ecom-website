<?php
session_start();
include 'config/database.php';

// Check if order ID is provided
if (!isset($_GET['order_id'])) {
    header('Location: index.php');
    exit();
}

$order_id = (int)$_GET['order_id'];

// Get order details
$query = "SELECT o.*, u.name as customer_name 
         FROM orders o 
         JOIN users u ON o.user_id = u.id 
         WHERE o.id = $order_id";
$result = mysqli_query($conn, $query);
$order = mysqli_fetch_assoc($result);

// Get order items
$items_query = "SELECT oi.*, p.name, p.image_url 
               FROM order_items oi 
               JOIN products p ON oi.product_id = p.id 
               WHERE oi.order_id = $order_id";
$items_result = mysqli_query($conn, $items_query);

include 'includes/header.php';
?>

<div class="success-container">
    <div class="container">
        <div class="success-card">
            <div class="success-header">
                <i class="fas fa-check-circle"></i>
                <h1>Order Placed Successfully!</h1>
                <p>Thank you for your purchase. Your order has been confirmed.</p>
            </div>

            <div class="order-details">
                <div class="order-info">
                    <h2>Order Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="label">Order Number:</span>
                            <span class="value">#<?php echo $order_id; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Order Date:</span>
                            <span class="value"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Payment Method:</span>
                            <span class="value"><?php echo ucfirst($order['payment_method']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Order Status:</span>
                            <span class="value status-<?php echo strtolower($order['status']); ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="shipping-info">
                    <h2>Shipping Address</h2>
                    <p><?php echo nl2br($order['shipping_address']); ?></p>
                </div>

                <div class="order-items">
                    <h2>Order Items</h2>
                    <div class="items-list">
                        <?php while ($item = mysqli_fetch_assoc($items_result)) { ?>
                            <div class="order-item">
                                <img src="<?php echo $item['image_url']; ?>" alt="<?php echo $item['name']; ?>">
                                <div class="item-details">
                                    <h3><?php echo $item['name']; ?></h3>
                                    <p class="quantity">Quantity: <?php echo $item['quantity']; ?></p>
                                    <p class="price">$<?php echo number_format($item['price'], 2); ?></p>
                                </div>
                                <div class="item-total">
                                    $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="order-summary">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>Free</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>

            <div class="success-actions">
                <a href="products.php" class="btn-continue">Continue Shopping</a>
                <a href="account.php?tab=orders" class="btn-orders">View Orders</a>
            </div>
        </div>
    </div>
</div>

<style>
.success-container {
    padding: 2rem 0;
    background: #f8f9fa;
}

.success-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 2rem;
    max-width: 800px;
    margin: 0 auto;
}

.success-header {
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #eee;
}

.success-header i {
    font-size: 4rem;
    color: #28a745;
    margin-bottom: 1rem;
}

.success-header h1 {
    margin-bottom: 1rem;
    color: #28a745;
}

.success-header p {
    color: #666;
}

.order-details {
    margin-bottom: 2rem;
}

.order-details h2 {
    margin-bottom: 1rem;
    font-size: 1.2rem;
    color: #333;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.info-item {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 4px;
}

.label {
    display: block;
    color: #666;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.value {
    font-weight: bold;
    color: #333;
}

.status-pending {
    color: #f39c12;
}

.status-processing {
    color: #3498db;
}

.status-completed {
    color: #28a745;
}

.status-cancelled {
    color: #e74c3c;
}

.shipping-info {
    margin-bottom: 2rem;
}

.shipping-info p {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 4px;
    line-height: 1.6;
}

.order-items {
    margin-bottom: 2rem;
}

.items-list {
    border: 1px solid #eee;
    border-radius: 4px;
}

.order-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #eee;
}

.order-item:last-child {
    border-bottom: none;
}

.order-item img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
}

.item-details {
    flex: 1;
    padding: 0 1rem;
}

.item-details h3 {
    margin-bottom: 0.5rem;
}

.quantity {
    color: #666;
    font-size: 0.9rem;
}

.price {
    color: #007bff;
    font-weight: bold;
}

.item-total {
    font-weight: bold;
    font-size: 1.1rem;
}

.order-summary {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 4px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
}

.summary-row.total {
    border-top: 1px solid #ddd;
    margin-top: 0.5rem;
    padding-top: 1rem;
    font-weight: bold;
    font-size: 1.2rem;
}

.success-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #eee;
}

.btn-continue,
.btn-orders {
    padding: 0.8rem 2rem;
    border-radius: 4px;
    text-decoration: none;
    font-weight: bold;
    transition: background 0.3s;
}

.btn-continue {
    background: #28a745;
    color: #fff;
}

.btn-continue:hover {
    background: #218838;
}

.btn-orders {
    background: #f8f9fa;
    color: #333;
    border: 1px solid #ddd;
}

.btn-orders:hover {
    background: #e9ecef;
}

@media (max-width: 768px) {
    .success-card {
        margin: 0 1rem;
    }

    .order-item {
        flex-direction: column;
        text-align: center;
    }

    .item-details {
        padding: 1rem 0;
    }

    .success-actions {
        flex-direction: column;
    }

    .btn-continue,
    .btn-orders {
        text-align: center;
    }
}
</style>

<?php include 'includes/footer.php'; ?>