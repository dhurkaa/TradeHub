<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id    = $_SESSION['user_id'];
    $title      = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $price      = floatval($_POST['price']);
    $category   = $conn->real_escape_string($_POST['category']);
    $condition  = $conn->real_escape_string($_POST['condition']);
    $location   = $conn->real_escape_string($_POST['location']);
    $contact    = $conn->real_escape_string($_POST['contact_info']);

    // Image upload
    $image_url = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newName = uniqid("img_") . '.' . $ext;
        $uploadPath = "uploads/" . $newName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
            $image_url = $uploadPath;
        }
    }

    $stmt = $conn->prepare("INSERT INTO products (user_id, title, description, price, category, `condition`, location, contact_info, image_url, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("issdsssss", $user_id, $title, $description, $price, $category, $condition, $location, $contact, $image_url);
    $stmt->execute();

    header("Location: home.php");
    exit();
}
?>
