<?php
session_start();
require_once 'config.php';

// Optional: make sure only admin can access
$admin_id = $_SESSION['user_id'] ?? 0;
if ($admin_id != 1) { // Replace 1 with your actual admin ID
    die("Access denied.");
}

$boosted = $conn->query("SELECT p.*, u.name FROM products p JOIN tradehub_users u ON p.user_id = u.id WHERE p.boosted_until > NOW() ORDER BY p.boosted_until DESC");
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Admin Boost Panel | TradeHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900 p-6">

<h1 class="text-2xl font-bold mb-6">🚀 Boosted Listings</h1>

<?php if ($boosted && $boosted->num_rows > 0): ?>
  <div class="overflow-x-auto">
    <table class="min-w-full bg-white rounded-xl shadow-md">
      <thead class="bg-gray-200 text-gray-700 text-sm">
        <tr>
          <th class="px-4 py-3 text-left">Product</th>
          <th class="px-4 py-3 text-left">Seller</th>
          <th class="px-4 py-3 text-left">Boosted Until</th>
          <th class="px-4 py-3 text-left">Placement</th>
          <th class="px-4 py-3 text-left">Reach</th>
          <th class="px-4 py-3 text-left">Views</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $boosted->fetch_assoc()): ?>
          <tr class="border-t text-sm">
            <td class="px-4 py-2 font-medium"><?= htmlspecialchars($row['title']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($row['name']) ?></td>
            <td class="px-4 py-2"><?= $row['boosted_until'] ?></td>
            <td class="px-4 py-2 capitalize"><?= $row['boost_placement'] ?? '—' ?></td>
            <td class="px-4 py-2">
              <?php
              if (isset($row['range_km'])) {
                  echo $row['range_km'] . " km";
              } else {
                  echo "—";
              }
              ?>
            </td>
            <td class="px-4 py-2"><?= $row['view_count'] ?? 0 ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <p class="text-gray-500">No currently boosted products.</p>
<?php endif; ?>

</body>
</html>
