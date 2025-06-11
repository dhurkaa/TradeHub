<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user details for the email
$userQuery = $conn->prepare("SELECT name, email FROM tradehub_users WHERE id = ?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$userInfo = $userQuery->get_result()->fetch_assoc();
$user_name = $userInfo['name'];
$user_email = $userInfo['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $uploadDir = 'uploads/verification/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filename = uniqid("id_") . "_" . basename($_FILES["document"]["name"]);
    $targetFile = $uploadDir . $filename;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];

    if (!in_array($fileType, $allowed)) {
        die("Invalid file type.");
    }

    if (move_uploaded_file($_FILES["document"]["tmp_name"], $targetFile)) {
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO seller_verification_requests (user_id, document_path) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $targetFile);

        $stmt->execute();

        // Send notification email to admin
        $admin_email = "dhurimcitaku2@gmail.com"; // ← Replace with your real email
        $subject = "🔔 New Seller Verification Request";
        $body = "Hello Admin,\n\nA new seller verification request has been submitted.\n\n"
              . "👤 Name: $user_name\n"
              . "📧 Email: $user_email\n"
              . "🆔 User ID: $user_id\n"
              . "📎 Document: https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/' . $targetFile . "\n\n"
              . "Please review it in the admin panel or via the uploaded file URL.\n\n"
              . "- TradeHub Bot";

        $headers = "From: no-reply@" . $_SERVER['SERVER_NAME'];

        mail($admin_email, $subject, $body, $headers);

        header("Location: account.php?status=submitted");
        exit();
    } else {
        die("File upload failed.");
    }
} else {
    header("Location: account.php");
    exit();
}
