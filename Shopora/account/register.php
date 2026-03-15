<?php
require_once '../database/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// If already logged in, no need to register again
if (getLoggedInUser()) {
    header('Location: /Shopora/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($conn->real_escape_string($_POST['first_name']));
    $last  = trim($conn->real_escape_string($_POST['last_name']));
    $email = trim($conn->real_escape_string($_POST['email']));
    $phone = trim($conn->real_escape_string($_POST['phone'] ?? ''));
    $pass  = $_POST['password'];
    $pass2 = $_POST['password_confirm'];

    // Server-side validation
    if (!$first || !$last || !$email || !$pass) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== $pass2) {
        $error = 'Passwords do not match.';
    } else {
        $check = $conn->query("SELECT user_id FROM customers WHERE email = '$email'");
        if ($check->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);

            $conn->query("
                INSERT INTO customers (first_name, last_name, email, phone, password)
                VALUES ('$first', '$last', '$email', '$phone', '$hashed')
            ");

            $new = $conn->query("SELECT * FROM customers WHERE email = '$email'")->fetch_assoc();

            // Store customer data in session to log them in immediately
            $_SESSION['user_id']    = $new['user_id'];
            $_SESSION['first_name'] = $new['first_name'];
            $_SESSION['last_name']  = $new['last_name'];
            $_SESSION['email']      = $new['email'];
            $_SESSION['avatar']     = '';

            // Redirect to home — welcome bar will show their name
            header('Location: /Shopora/index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register &mdash; Shopora</title>
    <link rel="stylesheet" href="/Shopora/style.css">
</head>
<body>

<div class="auth-page">
    <div class="auth-card">

        <!-- Logo linking back to home -->
        <div class="auth-logo">
            <a href="/Shopora/index.php">
                <img src="/Shopora/images/shopora.webp" alt="Shopora" class="auth-logo-img">
                <span class="auth-logo-name">Shopora</span>
            </a>
        </div>

        <h2 class="auth-title">Create an Account</h2>
        <p class="auth-sub">Sign up to start shopping</p>

        <!-- Show error message if validation failed -->
        <?php if ($error): ?>
            <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>

            <div class="form-row">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name"
                           value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                           placeholder="e.g. George" required>
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name"
                           value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                           placeholder="e.g. Papadopoulos" required>
                </div>
            </div>

            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="you@example.com" required>
            </div>

            <div class="form-group">
                <label>Phone</label>
                <input type="tel" name="phone"
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                       placeholder="69XXXXXXXX">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password"
                           placeholder="At least 6 characters" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="password_confirm"
                           placeholder="Repeat password" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">Create Account</button>
        </form>

        <p class="auth-switch">
            Already have an account? <a href="/Shopora/account/login.php">Sign In</a>
        </p>

    </div>
</div>

</body>
</html>
