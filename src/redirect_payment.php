<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = intval($_POST['product_id']);
$duration = intval($_POST['duration']);
$placement = $_POST['placement'];
$range_km = intval($_POST['range_km']);
$method = $_POST['payment_method'];

// Base prices for duration
$price_map = [
    3 => 2.49,
    7 => 4.99,
    14 => 8.99
];

// Radius multipliers
$range_multiplier = [
    10 => 1,
    50 => 1.8,
    150 => 2.5
];

$base_price = $price_map[$duration] ?? 2.49;
$multiplier = $range_multiplier[$range_km] ?? 1;
$final_price = round($base_price * $multiplier, 2);

$boosted_until = date('Y-m-d H:i:s', strtotime("+{$duration} days"));

// Store boost data
$stmt = $conn->prepare("UPDATE products SET boosted_until = ?, boost_placement = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("ssii", $boosted_until, $placement, $product_id, $user_id);
$stmt->execute();

// Redirect based on payment method
switch ($method) {
    case 'paypal':
        header("Location: https://www.paypal.com/paypalme/dhurimcitaku/$final_price");
        break;
    case 'paysera':
        header("Location: https://pay.trdhub.com/?amount=$final_price");
        break;
    case 'revolut':
        header("Location: https://revolut.me/dhurimcitaku");
        break;
    default:
        echo "Unknown payment method.";
        exit();
}
exit();
