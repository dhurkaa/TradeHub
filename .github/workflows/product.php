<?php
require_once 'config.php';
session_start();

if (!isset($_GET['id'])) {
    die("Product ID not provided.");
}

$id = intval($_GET['id']);
$result = $conn->query("SELECT * FROM products WHERE id = $id");

if (!$result || $result->num_rows == 0) {
    die("Product not found.");
}

$product = $result->fetch_assoc();
$seller = $conn->query("SELECT name FROM tradehub_users WHERE id = " . intval($product['user_id']))->fetch_assoc();

// Decode JSON images array
$images = json_decode($product['images'], true) ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($product['title']) ?> | TradeHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .carousel::-webkit-scrollbar {
      display: none;
    }
  </style>
</head>
<body class="bg-gray-100 text-gray-900">
  <!-- Header -->
  <header class="bg-white border-b shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-3 flex flex-col sm:flex-row items-center justify-between">
      <div class="flex items-center gap-2">
        <h1 class="text-2xl font-bold text-black">TradeHub</h1>
        <span class="text-sm text-gray-400 hidden sm:inline">| Marketplace</span>
      </div>
      <div class="text-center text-sm text-gray-600 mt-2 sm:mt-0">
        👤 <?= htmlspecialchars($seller['name']) ?>
      </div>
      <nav class="mt-3 sm:mt-0 flex flex-wrap justify-center gap-2 text-sm">
        <a href="home.php" class="px-3 py-1 rounded hover:bg-gray-100">Home</a>
        <a href="inbox.php" class="px-3 py-1 rounded hover:bg-gray-100">Messages</a>
        <a href="account.php" class="px-3 py-1 rounded hover:bg-gray-100">Account</a>
        <a href="my_listings.php" class="px-3 py-1 rounded hover:bg-gray-100">My Listings</a>
        <a href="logout.php" class="px-3 py-1 text-red-600 hover:underline">Sign Out</a>
      </nav>
    </div>
  </header>

  <!-- Main Product Content -->
  <main class="max-w-4xl mx-auto px-4 py-10">
    <div class="bg-white rounded-lg shadow p-6">
      <!-- Carousel -->
     <?php if (!empty($images)): ?>
  <div class="relative w-full mb-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <?php foreach ($images as $img): ?>
        <img src="<?= htmlspecialchars($img) ?>" alt="Product Image"
             class="w-full h-auto max-h-[500px] object-contain rounded shadow border bg-white p-2">
      <?php endforeach; ?>
    </div>
  </div>
<?php else: ?>
  <img src="https://via.placeholder.com/400x300?text=No+Image" alt="Product"
       class="w-full h-auto object-contain rounded mb-6">
<?php endif; ?>


      <!-- Product Details -->
      <h2 class="text-2xl font-bold mb-2"><?= htmlspecialchars($product['title']) ?></h2>
      <p class="text-gray-600 text-sm mb-4">Listed by <strong><?= htmlspecialchars($seller['name']) ?></strong></p>

      <div class="grid gap-2 text-sm">
        <p><strong>Price:</strong> $<?= number_format($product['price'], 2) ?></p>
        <p><strong>Category:</strong> <?= htmlspecialchars($product['category']) ?></p>
        <p><strong>Condition:</strong> <?= htmlspecialchars($product['condition']) ?></p>
        <p><strong>Location:</strong> <?= htmlspecialchars($product['location']) ?></p>
        <p><strong>Contact Info:</strong> <?= htmlspecialchars($product['contact_info']) ?></p>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold mb-2">Description:</h3>
        <p class="text-sm leading-relaxed bg-gray-50 p-3 rounded"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
      </div>

      <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $product['user_id']): ?>
        <a href="chat.php?product_id=<?= $product['id'] ?>"
           class="inline-block mt-6 bg-black text-white px-6 py-2 rounded hover:bg-gray-800 transition">💬 Message Seller</a>
      <?php endif; ?>
    </div>

    <div class="mt-8 text-center">
      <a href="home.php" class="text-blue-600 hover:underline">← Back to Marketplace</a>
    </div>
  </main>

  <!-- Footer -->
  <footer class="bg-gray-100 text-center text-sm text-gray-600 py-4 mt-12">
    &copy; <?= date('Y') ?> TradeHub. All rights reserved.
  </footer>
</body>
</html>
