<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch the latest unread message for popup
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
    // Optional: mark as read so it doesn't pop up again
    $mark = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
    $mark->bind_param("i", $msg['id']);
    $mark->execute();

    echo json_encode($msg);
} else {
    echo json_encode([]);
}
