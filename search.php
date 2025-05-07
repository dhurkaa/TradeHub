<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT name FROM tradehub_users WHERE id = $user_id")->fetch_assoc();

$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$searchTermSafe = $conn->real_escape_string($searchTerm);

// Query products by title, description, or category
$productResult = $conn->query("
  SELECT * FROM products 
  WHERE title LIKE '%$searchTermSafe%' 
     OR description LIKE '%$searchTermSafe%' 
     OR category LIKE '%$searchTermSafe%'
  ORDER BY created_at DESC
");

$color_classes = ['blue', 'orange', 'purple', 'sky'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Search Results - <?= htmlspecialchars($searchTerm) ?></title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #fff;
      color: #333;
    }
    header {
      background-color: #000;
      color: #fff;
      padding: 15px 0;
    }
    .container {
      width: 90%;
      max-width: 1100px;
      margin: auto;
    }
    .header-flex {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .header-flex h1 {
      margin: 0;
      font-size: 24px;
    }
    nav a {
      margin-left: 20px;
      text-decoration: none;
      color: #fff;
      font-weight: 500;
    }
    .search-bar {
      margin: 40px 0;
      display: flex;
      justify-content: center;
    }
    .search-bar input {
      padding: 10px;
      width: 60%;
      border: 1px solid #ccc;
      border-radius: 8px 0 0 8px;
      font-size: 16px;
    }
    .search-bar button {
      padding: 10px 20px;
      border: none;
      background: #000;
      color: #fff;
      border-radius: 0 8px 8px 0;
      cursor: pointer;
    }

    main h2 {
      font-size: 26px;
      margin: 40px 0 25px;
    }

    .product-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
    }
    .product-card {
      background-color: #f5f5f5;
      padding: 20px;
      border-radius: 16px;
      text-align: center;
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    }
    .product-card img {
      width: 100px;
      height: 100px;
      margin-bottom: 15px;
      border-radius: 12px;
      object-fit: cover;
    }
    .product-card h3 {
      font-size: 18px;
      margin: 10px 0 5px;
    }
    .product-card p {
      margin: 5px 0;
      color: #333;
    }
    .product-card button {
      margin-top: 10px;
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
    }
    .blue    { background-color: #d7e8ff; }
    .orange  { background-color: #ffe3b3; }
    .purple  { background-color: #ead7ff; }
    .sky     { background-color: #d4f1ff; }

    .blue button,
    .purple button {
      background-color: #4a4aff;
      color: #fff;
    }

    .orange button,
    .sky button {
      background-color: #ff7b2e;
      color: #fff;
    }

    .blue button:hover,
    .purple button:hover {
      background-color: #3737e0;
    }

    .orange button:hover,
    .sky button:hover {
      background-color: #e85e0c;
    }
  </style>
</head>
<body>
  <header>
    <div class="container header-flex">
      <h1>TradeHub</h1>
      <nav>
        <a href="home.php">Home</a>
        <a href="list_product.php">List a Product</a>
        <a href="logout.php">Sign out</a>
      </nav>
    </div>
  </header>

  <main class="container">
    <div class="search-bar">
      <form method="GET" action="search.php" style="display: flex; width: 100%; max-width: 600px;">
        <input type="text" name="q" placeholder="Search for products" value="<?= htmlspecialchars($searchTerm) ?>">
        <button type="submit">Search</button>
      </form>
    </div>

    <h2>Results for "<?= htmlspecialchars($searchTerm) ?>"</h2>

    <?php if ($productResult && $productResult->num_rows > 0): ?>
      <div class="product-grid">
        <?php $i = 0; ?>
        <?php while ($product = $productResult->fetch_assoc()): ?>
          <?php $color = $color_classes[$i % count($color_classes)]; ?>
          <div class="product-card <?= $color ?>">
            <img src="<?= !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'https://via.placeholder.com/100x100?text=No+Image' ?>" alt="Product Image">
            <h3><?= htmlspecialchars($product['title']) ?></h3>
            <p>Price: $<?= number_format($product['price'], 2) ?></p>
            <button onclick="location.href='product.php?id=<?= $product['id'] ?>'">View</button>
          </div>
          <?php $i++; ?>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <p>No products found for this search.</p>
    <?php endif; ?>
  </main>
</body>
</html>
