<?php
session_start();
include '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header('Location: ../login.php');
    exit();
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_site':
                $site_name = mysqli_real_escape_string($conn, $_POST['site_name']);
                $site_email = mysqli_real_escape_string($conn, $_POST['site_email']);
                $site_phone = mysqli_real_escape_string($conn, $_POST['site_phone']);
                $site_address = mysqli_real_escape_string($conn, $_POST['site_address']);
                
                // Update site settings
                $queries = [
                    "UPDATE settings SET value = '$site_name' WHERE name = 'site_name'",
                    "UPDATE settings SET value = '$site_email' WHERE name = 'site_email'",
                    "UPDATE settings SET value = '$site_phone' WHERE name = 'site_phone'",
                    "UPDATE settings SET value = '$site_address' WHERE name = 'site_address'"
                ];

                foreach ($queries as $query) {
                    mysqli_query($conn, $query);
                }
                break;

            case 'update_payment':
                $currency = mysqli_real_escape_string($conn, $_POST['currency']);
                $payment_methods = isset($_POST['payment_methods']) ? implode(',', $_POST['payment_methods']) : '';
                
                $queries = [
                    "UPDATE settings SET value = '$currency' WHERE name = 'currency'",
                    "UPDATE settings SET value = '$payment_methods' WHERE name = 'payment_methods'"
                ];

                foreach ($queries as $query) {
                    mysqli_query($conn, $query);
                }
                break;

            case 'update_shipping':
                $shipping_methods = isset($_POST['shipping_methods']) ? json_encode($_POST['shipping_methods']) : '[]';
                
                $query = "UPDATE settings SET value = '$shipping_methods' WHERE name = 'shipping_methods'";
                mysqli_query($conn, $query);
                break;

            case 'update_social':
                $facebook = mysqli_real_escape_string($conn, $_POST['facebook']);
                $twitter = mysqli_real_escape_string($conn, $_POST['twitter']);
                $instagram = mysqli_real_escape_string($conn, $_POST['instagram']);
                
                $queries = [
                    "UPDATE settings SET value = '$facebook' WHERE name = 'social_facebook'",
                    "UPDATE settings SET value = '$twitter' WHERE name = 'social_twitter'",
                    "UPDATE settings SET value = '$instagram' WHERE name = 'social_instagram'"
                ];

                foreach ($queries as $query) {
                    mysqli_query($conn, $query);
                }
                break;
        }

        $_SESSION['success'] = 'Settings updated successfully.';
        header('Location: settings.php');
        exit();
    }
}

// Get current settings
$query = "SELECT * FROM settings";
$result = mysqli_query($conn, $query);
$settings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['name']] = $row['value'];
}

include 'header.php';
?>

<div class="admin-settings">
    <div class="page-header">
        <h1>Website Settings</h1>
    </div>

    <?php if (isset($_SESSION['success'])) { ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php } ?>

    <div class="settings-container">
        <div class="settings-nav">
            <button class="nav-link active" data-tab="site">Site Information</button>
            <button class="nav-link" data-tab="payment">Payment Settings</button>
            <button class="nav-link" data-tab="shipping">Shipping Methods</button>
            <button class="nav-link" data-tab="social">Social Media</button>
        </div>

        <div class="settings-content">
            <!-- Site Information -->
            <div class="settings-tab active" id="site">
                <form action="settings.php" method="POST">
                    <input type="hidden" name="action" value="update_site">
                    
                    <div class="form-group">
                        <label for="site_name">Site Name</label>
                        <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="site_email">Contact Email</label>
                        <input type="email" id="site_email" name="site_email" value="<?php echo htmlspecialchars($settings['site_email'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="site_phone">Contact Phone</label>
                        <input type="text" id="site_phone" name="site_phone" value="<?php echo htmlspecialchars($settings['site_phone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="site_address">Business Address</label>
                        <textarea id="site_address" name="site_address"><?php echo htmlspecialchars($settings['site_address'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Save Changes</button>
                </form>
            </div>

            <!-- Payment Settings -->
            <div class="settings-tab" id="payment">
                <form action="settings.php" method="POST">
                    <input type="hidden" name="action" value="update_payment">
                    
                    <div class="form-group">
                        <label for="currency">Currency</label>
                        <select id="currency" name="currency">
                            <option value="USD" <?php echo ($settings['currency'] ?? '') == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                            <option value="EUR" <?php echo ($settings['currency'] ?? '') == 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                            <option value="GBP" <?php echo ($settings['currency'] ?? '') == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Payment Methods</label>
                        <?php
                        $active_methods = explode(',', $settings['payment_methods'] ?? '');
                        $available_methods = [
                            'credit_card' => 'Credit Card',
                            'paypal' => 'PayPal',
                            'bank_transfer' => 'Bank Transfer',
                            'cash_on_delivery' => 'Cash on Delivery'
                        ];
                        foreach ($available_methods as $value => $label) {
                        ?>
                            <div class="checkbox">
                                <input type="checkbox" id="payment_<?php echo $value; ?>" name="payment_methods[]" value="<?php echo $value; ?>" 
                                       <?php echo in_array($value, $active_methods) ? 'checked' : ''; ?>>
                                <label for="payment_<?php echo $value; ?>"><?php echo $label; ?></label>
                            </div>
                        <?php } ?>
                    </div>

                    <button type="submit" class="btn-submit">Save Changes</button>
                </form>
            </div>

            <!-- Shipping Methods -->
            <div class="settings-tab" id="shipping">
                <form action="settings.php" method="POST">
                    <input type="hidden" name="action" value="update_shipping">
                    
                    <div class="shipping-methods">
                        <?php
                        $shipping_methods = json_decode($settings['shipping_methods'] ?? '[]', true);
                        ?>
                        <div class="shipping-method-list">
                            <?php foreach ($shipping_methods as $index => $method) { ?>
                                <div class="shipping-method">
                                    <div class="form-group">
                                        <label>Method Name</label>
                                        <input type="text" name="shipping_methods[<?php echo $index; ?>][name]" value="<?php echo htmlspecialchars($method['name']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Price</label>
                                        <input type="number" name="shipping_methods[<?php echo $index; ?>][price]" value="<?php echo htmlspecialchars($method['price']); ?>" step="0.01" required>
                                    </div>
                                    <button type="button" class="btn-remove" onclick="removeShippingMethod(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            <?php } ?>
                        </div>
                        <button type="button" class="btn-add" onclick="addShippingMethod()">
                            <i class="fas fa-plus"></i> Add Shipping Method
                        </button>
                    </div>

                    <button type="submit" class="btn-submit">Save Changes</button>
                </form>
            </div>

            <!-- Social Media -->
            <div class="settings-tab" id="social">
                <form action="settings.php" method="POST">
                    <input type="hidden" name="action" value="update_social">
                    
                    <div class="form-group">
                        <label for="facebook">Facebook URL</label>
                        <input type="url" id="facebook" name="facebook" value="<?php echo htmlspecialchars($settings['social_facebook'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="twitter">Twitter URL</label>
                        <input type="url" id="twitter" name="twitter" value="<?php echo htmlspecialchars($settings['social_twitter'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="instagram">Instagram URL</label>
                        <input type="url" id="instagram" name="instagram" value="<?php echo htmlspecialchars($settings['social_instagram'] ?? ''); ?>">
                    </div>

                    <button type="submit" class="btn-submit">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.admin-settings {
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

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.settings-container {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    overflow: hidden;
}

.settings-nav {
    display: flex;
    border-bottom: 1px solid #ddd;
    background: #f8f9fa;
    overflow-x: auto;
}

.nav-link {
    padding: 1rem 1.5rem;
    border: none;
    background: none;
    cursor: pointer;
    white-space: nowrap;
    color: #666;
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
}

.nav-link.active {
    color: #007bff;
    border-bottom-color: #007bff;
    background: #fff;
}

.settings-content {
    padding: 2rem;
}

.settings-tab {
    display: none;
}

.settings-tab.active {
    display: block;
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
input[type="email"],
input[type="url"],
input[type="number"],
select,
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

.checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0.5rem 0;
}

.checkbox input {
    width: auto;
}

.checkbox label {
    margin: 0;
}

.shipping-methods {
    margin-bottom: 1.5rem;
}

.shipping-method {
    display: grid;
    grid-template-columns: 2fr 1fr auto;
    gap: 1rem;
    align-items: start;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 4px;
    margin-bottom: 1rem;
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
    margin-bottom: 1rem;
}

.btn-remove {
    background: #dc3545;
    color: #fff;
    border: none;
    border-radius: 4px;
    padding: 0.8rem;
    cursor: pointer;
}

.btn-submit {
    background: #007bff;
    color: #fff;
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    width: 100%;
}

@media (max-width: 768px) {
    .settings-nav {
        flex-direction: column;
    }

    .nav-link {
        width: 100%;
        text-align: left;
    }

    .shipping-method {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const navLinks = document.querySelectorAll('.nav-link');
    const tabs = document.querySelectorAll('.settings-tab');

    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            const tabId = link.dataset.tab;

            navLinks.forEach(l => l.classList.remove('active'));
            tabs.forEach(t => t.classList.remove('active'));

            link.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
});

// Shipping methods management
function addShippingMethod() {
    const list = document.querySelector('.shipping-method-list');
    const index = list.children.length;
    
    const method = document.createElement('div');
    method.className = 'shipping-method';
    method.innerHTML = `
        <div class="form-group">
            <label>Method Name</label>
            <input type="text" name="shipping_methods[${index}][name]" required>
        </div>
        <div class="form-group">
            <label>Price</label>
            <input type="number" name="shipping_methods[${index}][price]" step="0.01" required>
        </div>
        <button type="button" class="btn-remove" onclick="removeShippingMethod(this)">
            <i class="fas fa-trash"></i>
        </button>
    `;

    list.appendChild(method);
}

function removeShippingMethod(button) {
    button.closest('.shipping-method').remove();
}
</script>

<?php include 'footer.php'; ?>