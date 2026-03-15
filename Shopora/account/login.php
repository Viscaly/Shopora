<?php
require_once '../database/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (getLoggedInUser()) {
    header('Location: /Shopora/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($conn->real_escape_string($_POST['email']));
    $pass  = $_POST['password'];

    if (!$email || !$pass) {
        $error = 'Please enter your email and password.';
    } else {

        $result = $conn->query("SELECT * FROM customers WHERE email = '$email'");

        if ($result->num_rows === 1) {
            $customer = $result->fetch_assoc();


            if (password_verify($pass, $customer['password'])) {

                // Credentials correct — store in session
                $_SESSION['user_id']    = $customer['user_id'];
                $_SESSION['first_name'] = $customer['first_name'];
                $_SESSION['last_name']  = $customer['last_name'];
                $_SESSION['email']      = $customer['email'];
                $_SESSION['avatar']     = $customer['avatar'] ?? '';
                header('Location: /Shopora/index.php');
                exit;

            } else {
                $error = 'Incorrect password.';
            }
        } else {
            $error = 'No account found with that email.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In &mdash; Shopora</title>
    <link rel="stylesheet" href="/Shopora/style.css">
</head>
<body>

<div class="auth-page">
    <div class="auth-card">

        <div class="auth-logo">
            <a href="/Shopora/index.php">
                <img src="/Shopora/images/shopora.webp" alt="Shopora" class="auth-logo-img">
                <span class="auth-logo-name">Shopora</span>
            </a>
        </div>

        <h2 class="auth-title">Welcome back!</h2>
        <p class="auth-sub">Sign in to your account to continue</p>

        <!-- Show error message if login failed -->
        <?php if ($error): ?>
            <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="you@example.com" required autofocus>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password"
                       placeholder="Your password" required>
            </div>

            <button type="submit" class="btn-submit">Sign In</button>
        </form>

        <p class="auth-switch">
            Don't have an account? <a href="/Shopora/account/register.php">Register for free</a>
        </p>

    </div>
</div>

</body>
</html>
