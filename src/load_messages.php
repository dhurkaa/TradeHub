<?php
session_start();
require_once 'config.php';

$currentUser = $_SESSION['user_id'];
$chatWith = isset($_GET['user']) ? (int)$_GET['user'] : 0;

if (!$chatWith) exit();

$stmt = $conn->prepare("
    SELECT * FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) 
    ORDER BY timestamp ASC
");
$stmt->bind_param("iiii", $currentUser, $chatWith, $chatWith, $currentUser);
$stmt->execute();
$result = $stmt->get_result();
$conn->query("UPDATE messages SET is_read = 1 WHERE receiver_id = $currentUser AND sender_id = $chatWith");


while ($msg = $result->fetch_assoc()):
?>
<div class="mb-2 <?= $msg['sender_id'] == $currentUser ? 'text-right' : 'text-left' ?>">
  <span class="inline-block px-3 py-1 rounded-full <?= $msg['sender_id'] == $currentUser ? 'bg-black text-white' : 'bg-gray-300' ?>">
    <?= htmlspecialchars($msg['message']) ?>
  </span>
</div>
<?php endwhile; ?>
