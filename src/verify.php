<?php
session_start();
include 'config.php';

if (!isset($_GET['token'])) {
  die("Invalid request.");
}

$token = $_GET['token'];

$stmt = $conn->prepare("SELECT id FROM tradehub_users WHERE verify_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
  $user = $result->fetch_assoc();
  $user_id = $user['id'];

  $update = $conn->prepare("UPDATE tradehub_users SET is_verified = 1, verify_token = NULL WHERE id = ?");
  $update->bind_param("i", $user_id);
  $update->execute();

  echo "<h2>Your account has been verified! <a href='login.php'>Login here</a></h2>";
} else {
  echo "<h2>Invalid or expired verification link.</h2>";
}
?>
