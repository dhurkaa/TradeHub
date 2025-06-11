<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = intval($_POST['product_id']);
$days = intval($_POST['duration']);
$place = $_POST['placement'];
$payment = $_POST['payment_method'];

$boosted_until = date('Y-m-d H:i:s', strtotime("+$days days"));

$stmt = $conn->prepare("UPDATE products SET boosted_until = ?, boost_placement = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("ssii", $boosted_until, $place, $product_id, $user_id);
$stmt->execute();

// Fetch product + user email
$product = $conn->query("SELECT title FROM products WHERE id = $product_id")->fetch_assoc();
$user = $conn->query("SELECT email FROM tradehub_users WHERE id = $user_id")->fetch_assoc();

// Send confirmation email
$to = $user['email'];
$subject = "✅ Boost Confirmed on TradeHub";
$message = "Your product \"" . $product['title'] . "\" has been boosted for $days days via $payment.";
$headers = "From: boost@tradehub.com";
mail($to, $subject, $message, $headers);

header("Location: my_listings.php?boost=success");
exit();
