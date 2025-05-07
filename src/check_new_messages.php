<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) exit();

$user_id = $_SESSION['user_id'];

// Get the latest unread message
$stmt = $conn->prepare("
    SELECT m.id, m.message, m.sender_id, u.name
    FROM messages m
    JOIN tradehub_users u ON m.sender_id = u.id
    WHERE m.receiver_id = ? AND m.is_read = 0
    ORDER BY m.timestamp DESC
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($msg = $result->fetch_assoc()) {
    echo json_encode($msg);
} else {
    echo json_encode([]);
}
