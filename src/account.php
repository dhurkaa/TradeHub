<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, email FROM tradehub_users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    die("User not found.");
}

$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Account Settings | TradeHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
  <div class="max-w-3xl mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">👤 Account Settings</h1>

    <div class="bg-white rounded-lg shadow-md p-6">
      <p class="mb-4"><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></p>
      <p class="mb-4"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>

      <!-- Future fields -->
      <!-- <p class="mb-4"><strong>Location:</strong> <?= htmlspecialchars($user['location'] ?? 'Not set') ?></p> -->

      <div class="mt-6 space-x-4">
        <a href="change_password.php" class="text-blue-600 hover:underline">Change Password</a>
        <a href="delete_account.php" class="text-red-600 hover:underline">Delete Account</a>
      </div>
    </div>

    <div class="mt-6">
      <a href="home.php" class="text-gray-600 underline">← Back to Dashboard</a>
    </div>
  </div>
</body>
</html>
