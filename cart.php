<?php
session_start();
include 'config/database.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Handle cart actions
if (isset($_POST['action'])) {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    switch ($_POST['action']) {
        case 'add':
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = $quantity;
            }
            header('Location: cart.php');
            exit();

        case 'update':
            if ($quantity > 0) {
                $_SESSION['cart'][$product_id] = $quantity;
            } else {
                unset($_SESSION['cart'][$product_id]);
            }
            header('Location: cart.php');
            exit();

        case 'remove':
            unset($_SESSION['cart'][$product_id]);
            header('Location: cart.php');
            exit();
    }
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
            'image_url' => $product['image_url'],
            'quantity' => $quantity,
            'subtotal' => $subtotal
        );
    }
}

include 'includes/header.php';
?>

<div class="cart-container">
    <div class="container">
        <h1>Shopping Cart</h1>

        <?php if (!empty($cart_items)) { ?>
            <div class="cart-content">
                <div class="cart-items">
                    <?php foreach ($cart_items as $item) { ?>
                        <div class="cart-item">
                            <img src="<?php echo $item['image_url']; ?>" alt="<?php echo $item['name']; ?>">
                            <div class="item-details">
                                <h3><?php echo $item['name']; ?></h3>
                                <p class="price">$<?php echo number_format($item['price'], 2); ?></p>
                            </div>
                            <div class="quantity-controls">
                                <form action="cart.php" method="POST" class="quantity-form">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <button type="button" class="quantity-btn" onclick="updateQuantity(this, -1)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="99" class="quantity-input">
                                    <button type="button" class="quantity-btn" onclick="updateQuantity(this, 1)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </form>
                            </div>
                            <div class="subtotal">
                                $<?php echo number_format($item['subtotal'], 2); ?>
                            </div>
                            <form action="cart.php" method="POST" class="remove-form">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="remove-btn">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    <?php } ?>
                </div>

                <div class="cart-summary">
                    <h2>Order Summary</h2>
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
                    <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
                    <a href="products.php" class="continue-shopping">Continue Shopping</a>
                </div>
            </div>
        <?php } else { ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <p>Your cart is empty</p>
                <a href="products.php" class="btn">Start Shopping</a>
            </div>
        <?php } ?>
    </div>
</div>

<style>
.cart-container {
    padding: 2rem 0;
}

.cart-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-top: 2rem;
}

.cart-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: #fff;
    border-radius: 8px;
    margin-bottom: 1rem;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.cart-item img {
    width: 100px;
    height: 100px;
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

.price {
    color: #007bff;
    font-weight: bold;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quantity-form {
    display: flex;
    align-items: center;
}

.quantity-btn {
    background: #f8f9fa;
    border: 1px solid #ddd;
    padding: 0.5rem;
    cursor: pointer;
    border-radius: 4px;
}

.quantity-input {
    width: 50px;
    text-align: center;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.subtotal {
    font-weight: bold;
    padding: 0 1rem;
}

.remove-btn {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    padding: 0.5rem;
}

.cart-summary {
    background: #fff;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    height: fit-content;
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

.checkout-btn {
    display: block;
    background: #28a745;
    color: #fff;
    text-align: center;
    padding: 1rem;
    border-radius: 4px;
    text-decoration: none;
    margin: 1rem 0;
    transition: background 0.3s;
}

.checkout-btn:hover {
    background: #218838;
}

.continue-shopping {
    display: block;
    text-align: center;
    color: #007bff;
    text-decoration: none;
}

.empty-cart {
    text-align: center;
    padding: 3rem;
}

.empty-cart i {
    font-size: 4rem;
    color: #6c757d;
    margin-bottom: 1rem;
}

.empty-cart .btn {
    display: inline-block;
    background: #007bff;
    color: #fff;
    padding: 0.8rem 2rem;
    border-radius: 4px;
    text-decoration: none;
    margin-top: 1rem;
}

@media (max-width: 768px) {
    .cart-content {
        grid-template-columns: 1fr;
    }

    .cart-item {
        flex-wrap: wrap;
    }

    .item-details {
        width: calc(100% - 100px);
    }

    .quantity-controls,
    .subtotal {
        width: 50%;
        padding: 1rem 0;
    }
}
</style>

<script>
function updateQuantity(button, change) {
    const form = button.closest('form');
    const input = form.querySelector('.quantity-input');
    const currentValue = parseInt(input.value);
    const newValue = currentValue + change;

    if (newValue >= 1 && newValue <= 99) {
        input.value = newValue;
        form.submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>