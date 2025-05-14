<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) exit;

$user_id = $_SESSION['user_id'];

// You can later improve this by tracking city or category history
$recommend = $conn->prepare("SELECT * FROM products WHERE user_id != ? ORDER BY RAND() LIMIT 6");
$recommend->bind_param("i", $user_id);
$recommend->execute();
$result = $recommend->get_result();

if ($result->num_rows > 0): ?>
  <h2 class="text-2xl font-bold mb-4">🔮 You Might Like These</h2>
  <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
  <?php
    $color_classes = ['bg-blue-100', 'bg-orange-100', 'bg-purple-100', 'bg-sky-100'];
    $i = 0;
    while ($product = $result->fetch_assoc()):
      $color = $color_classes[$i % count($color_classes)];
      $images = json_decode($product['images'] ?? '[]', true);
      $thumb = !empty($images[0]) ? htmlspecialchars($images[0]) : 'https://via.placeholder.com/150?text=No+Image';
  ?>
    <div class="rounded-xl shadow-sm <?= $color ?> hover:shadow-lg transition p-5 flex flex-col items-center text-center">
      <img src="<?= $thumb ?>" class="w-28 h-28 object-cover rounded-lg mb-4" />
      <h3 class="text-lg font-semibold"><?= htmlspecialchars($product['title']) ?></h3>
      <p class="text-gray-700 mt-1 mb-3">Price: $<?= number_format($product['price'], 2) ?></p>
      <a href="product.php?id=<?= $product['id'] ?>" class="bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition">View</a>
    </div>
  <?php $i++; endwhile; ?>
  </div>
<?php else: ?>
  <p class="text-gray-500 text-sm">No personalized suggestions found yet.</p>
<?php endif; ?>
