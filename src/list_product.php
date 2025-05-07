<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id     = $_SESSION['user_id'];
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price       = floatval($_POST['price']);
    $category    = trim($_POST['category']);
    $condition   = trim($_POST['condition']);
    $location    = trim($_POST['location']);
    $contact     = trim($_POST['contact_info']);

    $uploadedImages = [];
    $maxFiles = 8;

    if (!empty($title) && !empty($description) && $price > 0 && !empty($category) && !empty($condition) && !empty($location) && !empty($contact)) {
        if (!empty($_FILES['images']['name'][0])) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
                if ($index >= $maxFiles) break;

                $fileName = basename($_FILES['images']['name'][$index]);
                $safeName = time() . "_" . preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $fileName);
                $targetPath = $uploadDir . $safeName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedImages[] = $targetPath;
                }
            }
        }

        $imagesJson = json_encode($uploadedImages);

        $stmt = $conn->prepare("INSERT INTO products (user_id, title, description, price, category, `condition`, location, contact_info, images, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issdsssss", $user_id, $title, $description, $price, $category, $condition, $location, $contact, $imagesJson);

        if ($stmt->execute()) {
            header("Location: home.php");
            exit();
        } else {
            $error = "Error saving product. Try again.";
        }
    } else {
        $error = "Please fill in all fields correctly.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>List a Product | TradeHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">
  <div class="w-full max-w-2xl bg-white p-8 rounded-xl shadow-xl mt-10 mb-10">
    <h2 class="text-3xl font-bold mb-6 text-center">📦 List a Product</h2>

    <?php if (!empty($error)): ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-5">
      <input type="text" name="title" placeholder="Product Title" required class="w-full px-4 py-2 border rounded-lg">

      <textarea name="description" rows="4" placeholder="Full Product Description" required class="w-full px-4 py-2 border rounded-lg resize-y"></textarea>

      <input type="number" name="price" step="0.01" placeholder="Product Price ($)" required class="w-full px-4 py-2 border rounded-lg">

      <select name="category" required class="w-full px-4 py-2 border rounded-lg">
        <option value="">Select Category</option>
        <option>Electronics</option>
        <option>Clothing</option>
        <option>Home</option>
        <option>Accessories</option>
        <option>Sports</option>
        <option>Toys</option>
      </select>

      <select name="condition" required class="w-full px-4 py-2 border rounded-lg">
        <option value="">Select Condition</option>
        <option>New</option>
        <option>Used</option>
        <option>Refurbished</option>
      </select>

      <input type="text" name="location" placeholder="Your Location" required class="w-full px-4 py-2 border rounded-lg">
      <input type="text" name="contact_info" placeholder="Contact Info (Email or Phone)" required class="w-full px-4 py-2 border rounded-lg">

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Upload up to 8 Images</label>
        <input type="file" name="images[]" accept="image/*" multiple required class="w-full mb-2" onchange="previewImages(this)">
        <div id="preview" class="grid grid-cols-3 gap-2 mt-2"></div>
        <small class="text-gray-500 text-xs">JPG, PNG, or WEBP — Max 8 images</small>
      </div>

      <button type="submit"
              class="w-full bg-black text-white font-semibold py-3 rounded-lg hover:bg-gray-800 transition">
        🚀 Submit Product
      </button>
    </form>
  </div>

  <script>
    function previewImages(input) {
      const preview = document.getElementById('preview');
      preview.innerHTML = '';
      const files = input.files;

      if (files.length > 8) {
        alert("You can upload up to 8 images.");
        input.value = '';
        return;
      }

      Array.from(files).forEach(file => {
        const reader = new FileReader();
        reader.onload = e => {
          const img = document.createElement('img');
          img.src = e.target.result;
          img.className = "w-full h-24 object-cover rounded border";
          preview.appendChild(img);
        };
        reader.readAsDataURL(file);
      });
    }
  </script>
</body>
</html>
