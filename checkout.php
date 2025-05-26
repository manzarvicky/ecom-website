<?php
session_start();
include 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit();
}

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

// Get cart items
$cart_items = array();
$total = 0;

if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $ids_string = implode(',', $product_ids);
    
    $query = "SELECT * FROM products WHERE id IN ($ids_string)";
    $result = mysqli_query($conn, $query);

    while ($product = mysqli_fetch_assoc($result)) {
        $quantity = $_SESSION['cart'][$product['id']];
        $subtotal = $product['price'] * $quantity;
        $total += $subtotal;

        $cart_items[] = array(
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'subtotal' => $subtotal
        );
    }
}

// Process checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shipping_address = mysqli_real_escape_string($conn, $_POST['address']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);

    // Create order
    $user_id = $_SESSION['user_id'];
    $insert_order = "INSERT INTO orders (user_id, total_amount, shipping_address, payment_method) 
                     VALUES ($user_id, $total, '$shipping_address', '$payment_method')";

    if (mysqli_query($conn, $insert_order)) {
        $order_id = mysqli_insert_id($conn);

        // Add order items
        foreach ($cart_items as $item) {
            $product_id = $item['id'];
            $quantity = $item['quantity'];
            $price = $item['price'];

            $insert_item = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                           VALUES ($order_id, $product_id, $quantity, $price)";
            mysqli_query($conn, $insert_item);

            // Update product stock
            $update_stock = "UPDATE products 
                           SET stock = stock - $quantity 
                           WHERE id = $product_id";
            mysqli_query($conn, $update_stock);
        }

        // Clear cart
        unset($_SESSION['cart']);

        // Redirect to success page
        header('Location: order-success.php?order_id=' . $order_id);
        exit();
    }
}

include 'includes/header.php';
?>

<div class="checkout-container">
    <div class="container">
        <h1>Checkout</h1>

        <div class="checkout-content">
            <div class="checkout-form">
                <form method="POST" action="checkout.php">
                    <div class="form-section">
                        <h2>Shipping Address</h2>
                        <div class="form-group">
                            <label for="address">Full Address</label>
                            <textarea id="address" name="address" required rows="4"></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2>Payment Method</h2>
                        <div class="payment-methods">
                            <div class="payment-method">
                                <input type="radio" id="card" name="payment_method" value="card" required>
                                <label for="card">
                                    <i class="fas fa-credit-card"></i>
                                    Credit/Debit Card
                                </label>
                            </div>
                            <div class="payment-method">
                                <input type="radio" id="paypal" name="payment_method" value="paypal">
                                <label for="paypal">
                                    <i class="fab fa-paypal"></i>
                                    PayPal
                                </label>
                            </div>
                            <div class="payment-method">
                                <input type="radio" id="cod" name="payment_method" value="cod">
                                <label for="cod">
                                    <i class="fas fa-money-bill-wave"></i>
                                    Cash on Delivery
                                </label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="place-order-btn">Place Order</button>
                </form>
            </div>

            <div class="order-summary">
                <h2>Order Summary</h2>
                <div class="order-items">
                    <?php foreach ($cart_items as $item) { ?>
                        <div class="order-item">
                            <div class="item-info">
                                <span class="item-name"><?php echo $item['name']; ?></span>
                                <span class="item-quantity">x<?php echo $item['quantity']; ?></span>
                            </div>
                            <span class="item-price">$<?php echo number_format($item['subtotal'], 2); ?></span>
                        </div>
                    <?php } ?>
                </div>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>$<?php echo number_format($total, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span>Free</span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span>$<?php echo number_format($total, 2); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.checkout-container {
    padding: 2rem 0;
}

.checkout-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-top: 2rem;
}

.checkout-form {
    background: #fff;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.form-section {
    margin-bottom: 2rem;
}

.form-section h2 {
    margin-bottom: 1rem;
    font-size: 1.2rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.8rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
}

.payment-methods {
    display: grid;
    gap: 1rem;
}

.payment-method {
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
}

.payment-method input[type="radio"] {
    margin-right: 1rem;
}

.payment-method label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.payment-method i {
    font-size: 1.2rem;
}

.place-order-btn {
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

.place-order-btn:hover {
    background: #218838;
}

.order-summary {
    background: #fff;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    height: fit-content;
}

.order-items {
    margin-bottom: 1rem;
}

.order-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
}

.item-info {
    display: flex;
    gap: 0.5rem;
}

.item-quantity {
    color: #666;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 1rem 0;
    border-bottom: 1px solid #eee;
}

.summary-row.total {
    font-weight: bold;
    font-size: 1.2rem;
    border-bottom: none;
}

@media (max-width: 768px) {
    .checkout-content {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/footer.php'; ?>