<?php
session_start();
include 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if email already exists
        $check_query = "SELECT * FROM users WHERE email = '$email'";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Email already exists';
        } else {
            // Hash password and create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO users (name, email, password, role, created_at) 
                            VALUES ('$name', '$email', '$hashed_password', 'customer', NOW())";

            if (mysqli_query($conn, $insert_query)) {
                $success = 'Registration successful! Please login.';
                // Redirect to login page after 2 seconds
                header("refresh:2;url=login.php");
            } else {
                $error = 'Error creating account: ' . mysqli_error($conn);
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-box">
        <h2>Create Account</h2>
        <?php if($error) { ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php } ?>
        <?php if($success) { ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php } ?>
        <form method="POST" action="register.php" class="auth-form">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <small class="form-text">Password must be at least 6 characters long</small>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Register</button>
            </div>
            <div class="auth-links">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </form>
    </div>
</div>

<style>
.auth-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 80vh;
    padding: 2rem;
}

.auth-box {
    background: #fff;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 400px;
}

.auth-box h2 {
    text-align: center;
    margin-bottom: 2rem;
}

.auth-form .form-group {
    margin-bottom: 1.5rem;
}

.auth-form label {
    display: block;
    margin-bottom: 0.5rem;
    color: #333;
}

.auth-form input {
    width: 100%;
    padding: 0.8rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
}

.form-text {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #6c757d;
}

.auth-form button {
    width: 100%;
    padding: 1rem;
    background: #007bff;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.3s;
}

.auth-form button:hover {
    background: #0056b3;
}

.auth-links {
    text-align: center;
    margin-top: 1rem;
}

.auth-links a {
    color: #007bff;
    text-decoration: none;
}

.alert {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 4px;
    text-align: center;
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
</style>

<?php include 'includes/footer.php'; ?>