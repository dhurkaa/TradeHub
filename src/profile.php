<?php
require_once 'config.php';
session_start();

if (!isset($_GET['user_id'])) {
    die("Seller not specified.");
}

$seller_id = intval($_GET['user_id']);

// Fetch seller info
$stmt = $conn->prepare("
  SELECT u.name, u.email, u.profile_picture,
         (SELECT status FROM seller_verification_requests WHERE user_id = u.id ORDER BY submitted_at DESC LIMIT 1) AS verified_status
  FROM tradehub_users u
  WHERE u.id = ?
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$seller = $stmt->get_result()->fetch_assoc();

if (!$seller) {
    die("Seller not found.");
}

$profilePic = $seller['profile_picture']
  ? htmlspecialchars($seller['profile_picture'])
  : "https://ui-avatars.com/api/?name=" . urlencode($seller['name']) . "&background=random";

$isVerified = $seller['verified_status'] === 'approved';

// Fetch seller listings
$products = $conn->prepare("SELECT id, title, price, images FROM products WHERE user_id = ? ORDER BY created_at DESC");
$products->bind_param("i", $seller_id);
$products->execute();
$productResult = $products->get_result();

// Get product IDs to calculate reviews
$productIds = [];
while ($row = $productResult->fetch_assoc()) {
    $productIds[] = $row['id'];
    $productData[] = $row;
}
$productResult->data_seek(0); // reset for reuse

$avgRating = 0;
$reviewCount = 0;
$reviews = [];

if (!empty($productIds)) {
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $types = str_repeat('i', count($productIds));

    // Fetch avg rating and count
    $stmt = $conn->prepare("SELECT ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS total FROM product_reviews WHERE product_id IN ($placeholders)");
    $stmt->bind_param($types, ...$productIds);
    $stmt->execute();
    $ratingData = $stmt->get_result()->fetch_assoc();
    $avgRating = $ratingData['avg_rating'] ?? 0;
    $reviewCount = $ratingData['total'] ?? 0;

    // Fetch individual reviews
    $stmt2 = $conn->prepare("
      SELECT pr.rating, pr.comment, pr.created_at, u.name 
      FROM product_reviews pr 
      JOIN tradehub_users u ON pr.user_id = u.id 
      WHERE pr.product_id IN ($placeholders)
      ORDER BY pr.created_at DESC
    ");
    $stmt2->bind_param($types, ...$productIds);
    $stmt2->execute();
    $reviews = $stmt2->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($seller['name']) ?> | TradeHub Profile</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">
  <div class="max-w-5xl mx-auto px-4 py-10">
    <!-- Seller Info -->
    <div class="bg-white rounded-lg shadow p-6 mb-6 flex flex-col sm:flex-row items-center gap-6">
      <img src="<?= $profilePic ?>" class="w-24 h-24 rounded-full border object-cover">
      <div class="text-center sm:text-left">
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($seller['name']) ?>
          <?php if ($isVerified): ?>
            <span class="text-green-600 text-sm ml-2">✔ Verified</span>
          <?php endif; ?>
        </h1>
        <p class="text-gray-600 text-sm">Email: <?= htmlspecialchars($seller['email']) ?></p>
        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $seller_id): ?>
          <a href="chat.php?user=<?= $seller_id ?>" class="inline-block mt-2 text-white bg-black px-4 py-2 rounded hover:bg-gray-800">💬 Message Seller</a>
        <?php endif; ?>
        <?php if ($reviewCount > 0): ?>
          <p class="mt-2 text-sm text-yellow-600">⭐ <?= $avgRating ?> (<?= $reviewCount ?> reviews)</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Listings -->
    <div class="mb-10">
      <h2 class="text-xl font-semibold mb-4">📦 Listings by <?= htmlspecialchars($seller['name']) ?></h2>
      <?php if (!empty($productData)): ?>
        <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
          <?php foreach ($productData as $product):
            $images = json_decode($product['images'], true) ?: [];
            $thumbnail = !empty($images[0]) ? htmlspecialchars($images[0]) : "https://via.placeholder.com/300x200?text=No+Image";
          ?>
            <div class="bg-white rounded-lg shadow p-4 text-center">
              <img src="<?= $thumbnail ?>" alt="Product" class="w-full h-40 object-cover rounded mb-3 border">
              <h3 class="font-semibold text-lg"><?= htmlspecialchars($product['title']) ?></h3>
              <p class="text-gray-600 text-sm mb-2">$<?= number_format($product['price'], 2) ?></p>
              <a href="product.php?id=<?= $product['id'] ?>" class="text-sm text-blue-600 hover:underline">View Product</a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-gray-500">This seller hasn't listed anything yet.</p>
      <?php endif; ?>
    </div>

    <!-- Reviews -->
    <div class="mb-10">
      <h2 class="text-xl font-semibold mb-2">⭐ Reviews</h2>
      <?php if ($reviewCount > 0): ?>
        <div class="space-y-4">
          <?php while ($r = $reviews->fetch_assoc()): ?>
            <div class="bg-white p-4 rounded shadow-sm">
              <div class="flex items-center justify-between">
                <p class="font-semibold"><?= htmlspecialchars($r['name']) ?></p>
                <span class="text-yellow-500">⭐ <?= $r['rating'] ?></span>
              </div>
              <p class="text-sm text-gray-600 mt-1"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
              <p class="text-xs text-gray-400 mt-1"><?= date("F j, Y", strtotime($r['created_at'])) ?></p>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <p class="text-gray-500">This seller hasn't received any reviews yet.</p>
      <?php endif; ?>
    </div>

    <div class="text-center">
      <a href="home.php" class="text-blue-600 hover:underline">← Back to Marketplace</a>
    </div>
  </div>
</body>
</html>
