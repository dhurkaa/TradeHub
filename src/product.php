<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
session_start();
require_once 'lang.php';

$langCode = $_SESSION['lang'] ?? 'en';
$t = $lang[$langCode];

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT name FROM tradehub_users WHERE id = $user_id")->fetch_assoc();

if (!isset($_GET['id'])) {
    die("Product ID not provided.");
}

$id = intval($_GET['id']);
$result = $conn->query("SELECT * FROM products WHERE id = $id");

if (!$result || $result->num_rows == 0) {
    die("Product not found.");
}

$product = $result->fetch_assoc();

$sellerStmt = $conn->prepare("
  SELECT u.id, u.name, u.profile_picture,
         (SELECT COUNT(*) FROM products WHERE user_id = u.id) AS total_listings,
         (SELECT status FROM seller_verification_requests WHERE user_id = u.id ORDER BY submitted_at DESC LIMIT 1) AS verified_status
  FROM tradehub_users u
  WHERE u.id = ?
");
$sellerStmt->bind_param("i", $product['user_id']);
$sellerStmt->execute();
$seller = $sellerStmt->get_result()->fetch_assoc();

$profilePic = $seller['profile_picture']
    ? htmlspecialchars($seller['profile_picture'])
    : "https://ui-avatars.com/api/?name=" . urlencode($seller['name']) . "&background=random";

$isVerified = $seller['verified_status'] === 'approved';

$images = json_decode($product['images'], true) ?: [];

$reviewStmt = $conn->prepare("SELECT r.*, u.name FROM product_reviews r JOIN tradehub_users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$reviewStmt->bind_param("i", $id);
$reviewStmt->execute();
$reviews = $reviewStmt->get_result();

$avgResult = $conn->query("SELECT AVG(rating) as avg_rating FROM product_reviews WHERE product_id = $id");
$avgRow = $avgResult->fetch_assoc();
$avgRating = $avgRow['avg_rating'] !== null ? round($avgRow['avg_rating'], 1) : 0;

$userReviewed = $conn->query("SELECT 1 FROM product_reviews WHERE product_id = $id AND user_id = $user_id LIMIT 1")->num_rows > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($product['title']) ?> | TradeHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">
<header class="bg-white border-b shadow-sm sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 py-3 flex flex-col sm:flex-row items-center justify-between">
    <div class="flex items-center gap-2">
      <h1 class="text-2xl font-bold text-black">TradeHub</h1>
      <span class="text-sm text-gray-400 hidden sm:inline">| Marketplace</span>
    </div>
    <div class="text-center text-sm text-gray-600 mt-2 sm:mt-0">
      <span>👋 <?= $t['hello'] ?>, <strong><?= htmlspecialchars($user['name']) ?></strong></span>
    </div>
    <nav class="mt-3 sm:mt-0 flex flex-wrap justify-center gap-3 text-sm bg-gray-100 px-3 py-2 rounded-full shadow-inner border">
      <a href="home.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition bg-black text-white">🏠 <?= $t['home'] ?></a>
      <a href="inbox.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">💬 <?= $t['messages'] ?></a>
      <a href="account.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">👤 <?= $t['account'] ?></a>
      <a href="my_listings.php" class="px-4 py-2 rounded-full font-medium border border-black text-black hover:bg-black hover:text-white transition">📦 <?= $t['my_listings'] ?></a>
      <a href="list_product.php" class="px-4 py-2 rounded-full font-medium bg-black text-white hover:bg-gray-800 transition">➕ <?= $t['list'] ?></a>
      <a href="my_favorites.php" class="px-4 py-2 rounded-full font-medium bg-black text-white hover:bg-red-800 transition">❤️ Favorites</a>
      <a href="logout.php" class="px-4 py-2 rounded-full font-medium text-red-600 hover:underline">🚪 <?= $t['logout'] ?></a>
    </nav>
  </div>
</header>

<main class="max-w-4xl mx-auto px-4 py-10">
  <div class="bg-white rounded-lg shadow p-6">
    <?php if (!empty($images)): ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <?php foreach ($images as $img): ?>
          <img src="<?= htmlspecialchars($img) ?>" class="w-full max-h-[500px] object-contain rounded shadow border bg-white p-2" alt="Product Image">
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <h2 class="text-2xl font-bold mb-2"><?= htmlspecialchars($product['title']) ?></h2>

    <div class="flex items-center gap-4 mb-4">
      <img src="<?= $profilePic ?>" alt="Seller" class="w-12 h-12 rounded-full border object-cover">
      <div>
        <a href="profile.php?user_id=<?= $seller['id'] ?>" class="text-blue-600 font-medium hover:underline">
          <?= htmlspecialchars($seller['name']) ?>
        </a>
        <?php if ($isVerified): ?>
          <span class="text-green-600 ml-1 text-sm font-semibold">✔️ Verified Seller</span>
        <?php endif; ?>
        <div class="text-xs text-gray-500"><?= $seller['total_listings'] ?> listing<?= $seller['total_listings'] == 1 ? '' : 's' ?></div>
      </div>
    </div>

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

    <?php if ($user_id != $product['user_id']): ?>
      <a href="chat.php?product_id=<?= $product['id'] ?>"
         class="inline-block mt-6 bg-black text-white px-6 py-2 rounded hover:bg-gray-800 transition">💬 Message Seller</a>

      <form action="add_to_favorites.php" method="POST" class="inline-block ml-2">
        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
        <button type="submit"
          class="mt-6 bg-red-500 text-white px-6 py-2 rounded hover:bg-red-600 transition">❤️ Add to Favorites</button>
      </form>
    <?php endif; ?>

    <div class="mt-10">
      <h3 class="text-xl font-bold mb-4">Reviews (<?= $avgRating > 0 ? "$avgRating/5" : "No ratings yet" ?>)</h3>
      <?php while ($r = $reviews->fetch_assoc()): ?>
        <div class="border-t pt-4 pb-2">
          <strong><?= htmlspecialchars($r['name']) ?></strong> - ⭐ <?= $r['rating'] ?>/5
          <p class="text-sm text-gray-800 mt-1">"<?= htmlspecialchars($r['comment']) ?>"</p>
        </div>
      <?php endwhile; ?>

      <?php if (!$userReviewed && $product['user_id'] != $user_id): ?>
        <form action="submit_review.php" method="POST" class="mt-6 bg-gray-50 p-4 rounded">
          <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
          <label class="block mb-2 font-semibold">Your Rating (1-5):</label>
          <input type="number" name="rating" min="1" max="5" class="w-20 px-3 py-1 border rounded mb-3" required>
          <label class="block mb-2 font-semibold">Comment:</label>
          <textarea name="comment" rows="3" class="w-full px-3 py-2 border rounded mb-3" required></textarea>
          <button type="submit" class="bg-black text-white px-6 py-2 rounded hover:bg-gray-800">Submit Review</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</main>
<footer class="bg-gray-100 text-center text-sm text-gray-600 py-4 mt-12">
  &copy; <?= date('Y') ?> TradeHub. All rights reserved.
</footer>
</body>
</html>
