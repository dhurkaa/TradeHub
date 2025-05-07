<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'config.php';

$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT name FROM tradehub_users WHERE id = $user_id")->fetch_assoc();
$result = $conn->query("SELECT * FROM products WHERE user_id = $user_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Listings | TradeHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    setInterval(() => {
      const clock = document.getElementById('clock');
      if (clock) clock.textContent = new Date().toLocaleTimeString();
    }, 1000);
  </script>
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
        👋 Hello, <strong><?= htmlspecialchars($user['name']) ?></strong>
        <span class="hidden sm:inline ml-2">⏰ <span id="clock"></span></span>
      </div>
      <nav class="mt-3 sm:mt-0 flex flex-wrap justify-center gap-2 text-sm">
        <a href="home.php" class="px-3 py-1 rounded hover:bg-gray-100">Home</a>
        <a href="inbox.php" class="px-3 py-1 rounded hover:bg-gray-100">Messages</a>
        <a href="account.php" class="px-3 py-1 rounded hover:bg-gray-100">Account</a>
        <a href="my_listings.php" class="px-3 py-1 border border-black text-black rounded hover:bg-black hover:text-white">My Listings</a>
        <a href="list_product.php" class="px-3 py-1 bg-black text-white rounded hover:bg-gray-800">+ List Product</a>
        <a href="logout.php" class="px-3 py-1 text-red-600 hover:underline">Sign Out</a>
      </nav>
    </div>
  </header>

  <!-- Main Content -->
  <div class="max-w-6xl mx-auto px-4 py-10">
    <h1 class="text-3xl font-bold mb-6">📦 My Listings</h1>

    <?php if ($result && $result->num_rows > 0): ?>
      <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        <?php while ($product = $result->fetch_assoc()): ?>
          <?php
            $images = json_decode($product['images'], true);
            $thumbnail = !empty($images[0]) ? htmlspecialchars($images[0]) : 'https://via.placeholder.com/300?text=No+Image';
          ?>
          <div class="bg-white rounded-lg shadow-md p-4 text-center">
            <img src="<?= $thumbnail ?>" alt="Product Image"
                 class="w-full h-40 object-contain bg-white rounded mb-3 border">
            <h3 class="text-lg font-semibold"><?= htmlspecialchars($product['title']) ?></h3>
            <p class="text-gray-600 mt-1 mb-2">$<?= number_format($product['price'], 2) ?></p>
            <a href="product.php?id=<?= $product['id'] ?>"
               class="inline-block px-4 py-2 bg-black text-white rounded hover:bg-gray-800">View</a>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <p class="text-gray-600">You haven't listed any products yet.</p>
    <?php endif; ?>


  </div>

</body>
</html>
