<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['product_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = intval($_POST['product_id']);

// Prevent duplicate entries
$stmt = $conn->prepare("INSERT IGNORE INTO user_favorites (user_id, product_id) VALUES (?, ?)");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$stmt->close();

header("Location: product.php?id=$product_id");
exit();
