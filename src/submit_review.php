<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_POST['product_id']) || !isset($_POST['rating']) || !isset($_POST['comment'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = intval($_POST['product_id']);
$rating = max(1, min(5, intval($_POST['rating'])));
$comment = trim($_POST['comment']);

$stmt = $conn->prepare("INSERT INTO product_reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiis", $product_id, $user_id, $rating, $comment);
$stmt->execute();
$stmt->close();

header("Location: product.php?id=$product_id");
exit();
