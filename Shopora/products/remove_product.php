<?php
require_once '../database/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$user = getLoggedInUser();
if (!$user) {
    header('Location: /Shopora/account/login.php');
    exit;
}

$pid = (int)($_GET['id'] ?? 0);
$uid = (int)$user['user_id'];

if ($pid > 0) {
    $res = $conn->query("SELECT name, image_path, owner_id FROM products WHERE product_id = $pid");

    if ($res->num_rows > 0) {
        $product = $res->fetch_assoc();


        if ((int)$product['owner_id'] !== $uid) {
            header('Location: /Shopora/products/products.php');
            exit;
        }


        if (!empty($product['image_path'])) {
            $file = $_SERVER['DOCUMENT_ROOT'] . '/Shopora/images/products/' . $product['image_path'];
            if (file_exists($file)) unlink($file);
        }

        // Διαγραφή από τη βάση αφαιρεί σχετικές εγγραφές καλαθιού
        $conn->query("DELETE FROM products WHERE product_id = $pid");
        header('Location: /Shopora/products/edit_product.php?removed=' . urlencode($product['name']));
        exit;
    }
}

header('Location: /Shopora/products/edit_product.php');
exit;
