<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'shopora_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

function getLoggedInUser() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['user_id'])) {
        return [
            'user_id'    => $_SESSION['user_id'],
            'first_name' => $_SESSION['first_name'],
            'last_name'  => $_SESSION['last_name'],
            'email'      => $_SESSION['email'],
            'avatar'     => $_SESSION['avatar'] ?? '',
        ];
    }
    return null;
}
?>
