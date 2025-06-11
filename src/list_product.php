<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'config.php';
require_once 'lang.php';

$langCode = $_SESSION['lang'] ?? 'en';
$t = $lang[$langCode];

$user_id = $_SESSION['user_id'];
$userStmt = $conn->prepare("SELECT email, phone FROM tradehub_users WHERE id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userStmt->bind_result($email, $phone);
$userStmt->fetch();
$userStmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price       = floatval($_POST['price']);
    $category    = trim($_POST['category']);
    $condition   = trim($_POST['condition']);

    $uploadedImages = [];
    if (!empty($_FILES['images']['name'][0])) {
        $uploadDir = 'uploads/';
        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            $fileName = basename($_FILES['images']['name'][$key]);
            $targetPath = $uploadDir . time() . "_" . $fileName;
            if (move_uploaded_file($tmpName, $targetPath)) {
                $uploadedImages[] = $targetPath;
            }
        }
    }

    $imagesJson = json_encode($uploadedImages);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $geo = @json_decode(file_get_contents("http://ip-api.com/json/$ip_address"), true);
    $auto_location = (!empty($geo['city']) && !empty($geo['country']))
        ? $geo['city'] . ', ' . $geo['country']
        : 'Unknown';

    $contact = $email . " / " . $phone;

    if (!empty($title) && !empty($description) && $price > 0 && !empty($category) && !empty($condition)) {
        $stmt = $conn->prepare("INSERT INTO products (user_id, title, description, price, category, `condition`, location, contact_info, images, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issdsssss", $user_id, $title, $description, $price, $category, $condition, $auto_location, $contact, $imagesJson);
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
<html lang="<?= $langCode ?>">
<head>
  <meta charset="UTF-8" />
  <title>List Product | TradeHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
  </style>
  <script>
    setInterval(() => {
      const clock = document.getElementById('clock');
      if (clock) clock.textContent = new Date().toLocaleTimeString();
    }, 1000);
  </script>
</head>
<body class="bg-white text-gray-900">

<!-- HEADER (same as home.php) -->
<header class="bg-white border-b shadow-sm sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 py-3 flex flex-col sm:flex-row items-center justify-between">
    <div class="flex items-center gap-2">
      <h1 class="text-2xl font-bold text-black">TradeHub</h1>
      <span class="text-sm text-gray-400 hidden sm:inline">| Marketplace</span>
    </div>
    <div class="text-center text-sm text-gray-600 mt-2 sm:mt-0">
      👋 <?= $t['hello'] ?? 'Hello' ?>, <strong><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></strong>
      <span class="hidden sm:inline ml-2">⏰ <span id="clock"></span></span>
    </div>
    <nav class="mt-3 sm:mt-0 flex flex-wrap justify-center gap-3 text-sm bg-gray-100 px-3 py-2 rounded-full shadow-inner border">
      <a href="home.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">🏠 <?= $t['home'] ?? 'Home' ?></a>
      <a href="inbox.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">💬 <?= $t['messages'] ?? 'Messages' ?></a>
      <a href="account.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">👤 <?= $t['account'] ?? 'Account' ?></a>
      <a href="my_listings.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">📦 <?= $t['my_listings'] ?? 'My Listings' ?></a>
      <a href="my_favorites.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">❤️ <?= $t['favorites'] ?? 'Favorites' ?></a>
      <a href="logout.php" class="px-4 py-2 rounded-full font-medium text-red-600 hover:underline">🚪 <?= $t['logout'] ?? 'Logout' ?></a>
    </nav>
  </div>
</header>

<!-- Main Content -->
<main class="max-w-3xl mx-auto px-4 py-10">
  <h2 class="text-2xl font-bold mb-4">📦 <?= $t['list'] ?? 'List a Product' ?></h2>

  <?php if (!empty($error)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-xl shadow-md space-y-4">
    <input type="text" name="title" placeholder="Product Title" required class="w-full px-4 py-2 border border-gray-300 rounded-md" />

    <textarea name="description" rows="4" placeholder="Full Product Description" required class="w-full px-4 py-2 border border-gray-300 rounded-md"></textarea>

    <input type="number" name="price" step="0.01" placeholder="Product Price ($)" required class="w-full px-4 py-2 border border-gray-300 rounded-md" />

    <select name="category" required class="w-full px-4 py-2 border border-gray-300 rounded-md">
      <option value="">Select Category</option>
      <option>📱 Phones</option>
      <option>💻 Laptops</option>
      <option>📷 Cameras</option>
      <option>📺 TVs</option>
      <option>👕 Men</option>
      <option>👗 Women</option>
      <option>👶 Kids</option>
      <option>👟 Shoes</option>
      <option>🛋️ Furniture</option>
      <option>🍽️ Kitchen</option>
      <option>🪴 Garden</option>
      <option>🛏️ Bedding</option>
      <option>⌚ Watches</option>
      <option>👜 Bags</option>
      <option>🕶️ Sunglasses</option>
      <option>💍 Jewelry</option>
      <option>🏋️ Fitness</option>
      <option>🚲 Cycling</option>
      <option>⚽ Outdoor</option>
      <option>🏀 Equipment</option>
      <option>🧩 Educational</option>
      <option>🚗 RC Toys</option>
      <option>🎲 Games</option>
      <option>🔧 Car Parts</option>
      <option>🛠️ Tools</option>
      <option>🧽 Detailing</option>
      <option>💄 Makeup</option>
      <option>🧴 Skincare</option>
      <option>💇 Haircare</option>
      <option>🧾 POS Systems</option>
      <option>📦 Packaging</option>
      <option>🖨️ Office Supplies</option>
    </select>

    <select name="condition" required class="w-full px-4 py-2 border border-gray-300 rounded-md">
      <option value="">Select Condition</option>
      <option>New</option>
      <option>Used</option>
      <option>Refurbished</option>
    </select>

    <input type="file" id="images" name="images[]" accept="image/*" multiple required class="w-full" />
    <p class="text-sm text-gray-500">You can upload up to 8 images (JPG, PNG, WEBP).</p>
    <div id="preview" class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-2"></div>

    <button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-md font-semibold hover:bg-blue-700 transition">Submit Product</button>
  </form>
</main>

<script>
const imagesInput = document.getElementById('images');
const previewContainer = document.getElementById('preview');
let selectedFiles = [];

imagesInput.addEventListener('change', function () {
  previewContainer.innerHTML = '';
  selectedFiles = Array.from(this.files);

  selectedFiles.forEach((file, index) => {
    const reader = new FileReader();
    reader.onload = function (e) {
      const wrapper = document.createElement('div');
      wrapper.className = "relative";

      const img = document.createElement('img');
      img.src = e.target.result;
      img.className = "w-full h-24 object-cover rounded-md border";

      const removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.innerHTML = '×';
      removeBtn.className = "absolute -top-2 -right-2 bg-red-600 text-white w-6 h-6 rounded-full text-xs flex items-center justify-center hover:bg-red-700";
      removeBtn.onclick = () => {
        selectedFiles.splice(index, 1);
        updateFileInput();
      };

      wrapper.appendChild(img);
      wrapper.appendChild(removeBtn);
      previewContainer.appendChild(wrapper);
    };
    reader.readAsDataURL(file);
  });
});

function updateFileInput() {
  const dataTransfer = new DataTransfer();
  selectedFiles.forEach(file => dataTransfer.items.add(file));
  imagesInput.files = dataTransfer.files;
  imagesInput.dispatchEvent(new Event('change'));
}
</script>
</body>
</html>
