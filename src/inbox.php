<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$currentUser = $_SESSION['user_id'];

// Get unique senders who messaged this user, latest message only
$stmt = $conn->prepare("
  SELECT m.sender_id, u.name, MAX(m.timestamp) AS last_time, 
         (SELECT message FROM messages WHERE sender_id = m.sender_id AND receiver_id = ? ORDER BY timestamp DESC LIMIT 1) AS last_message
  FROM messages m
  JOIN tradehub_users u ON u.id = m.sender_id
  WHERE m.receiver_id = ?
  GROUP BY m.sender_id
  ORDER BY last_time DESC
");
$stmt->bind_param("ii", $currentUser, $currentUser);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Inbox | TradeHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
  <div class="max-w-3xl mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">📥 Inbox</h1>

    <?php if ($result && $result->num_rows > 0): ?>
      <ul class="space-y-4">
        <?php while ($row = $result->fetch_assoc()): ?>
          <li class="bg-white rounded-lg shadow p-4 flex justify-between items-center">
            <div>
              <p class="font-semibold"><?= htmlspecialchars($row['name']) ?></p>
              <p class="text-sm text-gray-600"><?= htmlspecialchars($row['last_message']) ?></p>
              <p class="text-xs text-gray-400 mt-1"><?= date('M d, H:i', strtotime($row['last_time'])) ?></p>
            </div>
            <a href="chat.php?user=<?= $row['sender_id'] ?>"
               class="text-white bg-black px-4 py-2 rounded hover:bg-gray-800">Reply</a>
          </li>
        <?php endwhile; ?>
      </ul>
    <?php else: ?>
      <p class="text-gray-600">You have no messages yet.</p>
    <?php endif; ?>

    <div class="mt-6">
      <a href="home.php" class="text-gray-600 underline">← Back to Dashboard</a>
    </div>
  </div>
</body>
</html>
