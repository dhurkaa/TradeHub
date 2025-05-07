<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$currentUser = $_SESSION['user_id'];
$chatWith = 0;
$chatHeader = '';
$showProduct = false;
$product = [];

// Handle chat from product
if (isset($_GET['product_id'])) {
    $product_id = (int)$_GET['product_id'];

    $stmt = $conn->prepare("SELECT p.*, u.name AS seller_name FROM products p JOIN tradehub_users u ON p.user_id = u.id WHERE p.id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $productResult = $stmt->get_result();

    if (!$productResult || $productResult->num_rows === 0) {
        die("Product not found.");
    }

    $product = $productResult->fetch_assoc();
    $chatWith = (int)$product['user_id'];

    if ($chatWith == $currentUser) {
        die("You cannot chat with yourself.");
    }

    $chatHeader = "Chat about " . htmlspecialchars($product['title']);
    $showProduct = true;

// Handle chat directly with a user
} elseif (isset($_GET['user'])) {
    $chatWith = (int)$_GET['user'];

    if ($chatWith == $currentUser) {
        die("You cannot chat with yourself.");
    }

    $userResult = $conn->query("SELECT name FROM tradehub_users WHERE id = $chatWith");
    if (!$userResult || $userResult->num_rows === 0) {
        die("User not found.");
    }

    $userRow = $userResult->fetch_assoc();
    $chatHeader = "Chat with " . htmlspecialchars($userRow['name']);
    $showProduct = false;

} else {
    die("No product or user specified.");
}

// Fetch chat messages
$stmt = $conn->prepare("
    SELECT * FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) 
    ORDER BY timestamp ASC
");
$stmt->bind_param("iiii", $currentUser, $chatWith, $chatWith, $currentUser);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

// Mark messages as read
$conn->query("UPDATE messages SET is_read = 1 WHERE receiver_id = $currentUser AND sender_id = $chatWith");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $chatHeader ?> | TradeHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    function sendMessage() {
      const msg = document.getElementById('message').value;
      if (msg.trim() === '') return;

      fetch('send_message.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'message=' + encodeURIComponent(msg) + '&receiver_id=<?= $chatWith ?>'
      })
      .then(() => {
        document.getElementById('message').value = '';
        loadMessages();
      });
    }

    function loadMessages() {
      fetch('load_messages.php?user=<?= $chatWith ?>')
        .then(res => res.text())
        .then(html => {
          document.getElementById('chatbox').innerHTML = html;
        });
    }

    setInterval(loadMessages, 3000);
  </script>
</head>
<body class="bg-gray-100">
  <div class="max-w-4xl mx-auto p-6">
    <!-- Chat Header -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
      <h2 class="text-xl font-bold"><?= $chatHeader ?></h2>
      <?php if ($showProduct): ?>
        <div class="flex items-center mt-3">
          <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="Product Image" class="w-20 h-20 object-cover rounded-lg mr-4">
          <div>
            <p class="font-semibold"><?= htmlspecialchars($product['title']) ?></p>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($product['location']) ?> - $<?= number_format($product['price'], 2) ?></p>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Chat Box -->
    <div class="bg-white rounded-lg shadow p-4">
      <div id="chatbox" class="h-64 overflow-y-auto mb-4 p-2 border rounded bg-gray-50">
        <?php foreach ($messages as $msg): ?>
          <div class="mb-2 <?= $msg['sender_id'] == $currentUser ? 'text-right' : 'text-left' ?>">
            <span class="inline-block px-3 py-1 rounded-full <?= $msg['sender_id'] == $currentUser ? 'bg-black text-white' : 'bg-gray-300' ?>">
              <?= htmlspecialchars($msg['message']) ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="flex">
        <input type="text" id="message" placeholder="Type a message..."
               class="flex-1 border px-4 py-2 rounded-l focus:outline-none">
        <button onclick="sendMessage()" class="bg-black text-white px-4 py-2 rounded-r hover:bg-gray-800">Send</button>
      </div>
    </div>
  </div>
</body>
</html>
