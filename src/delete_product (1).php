<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id > 0) {
    // Only delete if the product belongs to the current user
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $product_id, $user_id);
    $stmt->execute();

    // Optional: delete associated images from folder (if you want)
}

header("Location: my_listings.php");
exit();
?>
