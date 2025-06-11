<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = intval($_GET['id']);
$days = intval($_GET['days']);
$place = $_GET['place'] ?? 'homepage';

$boosted_until = date('Y-m-d H:i:s', strtotime("+$days days"));

// Add both boost duration and placement
$stmt = $conn->prepare("UPDATE products SET boosted_until = ?, boost_placement = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("ssii", $boosted_until, $place, $product_id, $user_id);
$stmt->execute();

header("Location: my_listings.php?boost=success");
exit();
