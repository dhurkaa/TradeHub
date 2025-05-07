<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['receiver_id']) || !isset($_POST['message'])) {
    exit("Unauthorized");
}

$sender = $_SESSION['user_id'];
$receiver = (int)$_POST['receiver_id'];
$message = trim($_POST['message']);

if ($message !== '') {
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, is_read) VALUES (?, ?, ?, 0)");
    $stmt->bind_param("iis", $sender, $receiver, $message);
    $stmt->execute();

    // Get receiver email
    $user = $conn->query("SELECT email FROM tradehub_users WHERE id = $receiver")->fetch_assoc();

    if ($user && isset($user['email'])) {
        $to = $user['email'];
        $subject = "New Message on TradeHub";
        $body = "You have received a new message from a buyer.\n\nLogin and go to https://cobifiles.site/tradehub/chat.php?user=$sender to reply.";
        $headers = "From: no-reply@tradehub.com\r\n";
        mail($to, $subject, $body, $headers);
    }
}
