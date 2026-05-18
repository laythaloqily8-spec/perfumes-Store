<?php
/**
 * Shop Page — Dynamic Product Catalogue
 *
 * Reads all perfumes from the `perfumes` table in the `perfume_store`
 * database and renders them using the exact same card layout as the
 * original static shop.html.
 */

// db.php is in the same directory — use a simple relative include
require_once 'db.php';

// --- Auto-migration: ensure required columns exist ---
// If the `category` column is missing from the `perfumes` table (schema change
// applied after some databases were already created), add it automatically so
// the SELECT query below does not throw "Unknown column" errors.
$check_col = $conn->query("SHOW COLUMNS FROM perfumes LIKE 'category'");
if ($check_col !== false && $check_col->num_rows === 0) {
    $conn->query("ALTER TABLE perfumes ADD category VARCHAR(50) NOT NULL DEFAULT 'unisex'");
}

// --- Read and sanitize GET parameters for filtering ---
$category_filter = isset($_GET['category']) && $_GET['category'] !== '' ? trim($_GET['category']) : '';
$search_query    = isset($_GET['search']) && $_GET['search'] !== '' ? trim($_GET['search']) : '';

// --- Build dynamic SQL with optional WHERE clauses ---
// Start with the base SELECT; conditions and bound params are added below.
$sql = 'SELECT id, name, description, price, image, category FROM perfumes';
$conditions = [];
$params = [];
$types = '';

// Category filter (skip pseudo-value "all" so it matches every row)
if ($category_filter !== '' && $category_filter !== 'all') {
    $conditions[] = 'category = ?';
    $params[] = $category_filter;
    $types .= 's';
}

// Search filter — match against name OR description using LIKE
if ($search_query !== '') {
    $conditions[] = '(name LIKE ? OR description LIKE ?)';
    $like = '%' . $search_query . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

// Append WHERE clause if any conditions exist
if (!empty($conditions)) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY id ASC';

// --- Execute the parameterised query ---
$products = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Normalize image path: strip any leading ../ so downstream
                // consumers always get clean paths like "images/filename.jpg"
                $row['image'] = preg_replace('#^(\.\./)+#', '', $row['image'] ?? '');
                $products[] = $row;
            }
        }
    }
    $stmt->close();
}
// If any step fails, $products remains an empty array and the HTML section
// renders the "No perfumes available." message with no fatal error.
?>
<!DOCTYPE html>
<html>
<head>
    <title>Adenor - Boutique Shopping</title>
    <meta charset="UTF-8">
    <!-- Paths prefixed with ../ because this file lives inside the php/ subdirectory -->
    <link type="text/css" rel="stylesheet" href="../css/shopStyle.css">
    <link type="text/css" rel="stylesheet" href="../css/shop-extras.css">
</head>
<body class="shop-body">

    <nav class="navbar-fixed">
        <ul class="nav1">
            <li><img src="../images/logoHOME.jpg" alt="Logo"></li>
            <li><a href="../index.html" >Home</a></li>
            <li><a href="shop.php" >Shop</a></li>
            <li><a href="../cart.html" class="cart-link">Cart (<span id="cart-count">0</span>)</a></li>
            <li><a href="../login.html" >Login</a></li>
        </ul>
    </nav>

    <div class="shop-header">
        <h2>Welcome to Adenor Shopping Boutique</h2>
        <p>Please choose the exclusive fragrances you want and press buy</p>
    </div>

    <div class="shop-controls">
        <input type="text" id="search-input" placeholder="Search perfumes by name or category..."
               value="<?= htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8') ?>">
        <div class="filter-buttons">
            <button class="filter-btn <?= $category_filter === '' || $category_filter === 'all' ? 'active' : '' ?>" data-filter="all">All Products</button>
            <button class="filter-btn <?= $category_filter === 'men' ? 'active' : '' ?>" data-filter="men">Men</button>
            <button class="filter-btn <?= $category_filter === 'women' ? 'active' : '' ?>" data-filter="women">Women</button>
            <button class="filter-btn <?= $category_filter === 'unisex' ? 'active' : '' ?>" data-filter="unisex">Unisex</button>
            <button class="filter-btn <?= $category_filter === 'luxury' ? 'active' : '' ?>" data-filter="luxury">Luxury</button>
        </div>
    </div>

    <form action="shopping.php" method="POST" id="shoppingForm">

        <div class="products-grid">

            <?php if (empty($products)): ?>
                <!-- No products in the database — show a clean fallback message -->
                <p style="text-align:center; padding:60px 20px; color:#5A2F16; font-size:18px; width:100%;">
                    No perfumes available.
                </p>
            <?php else: ?>
                <?php foreach ($products as $product):
                    // Sanitize output to prevent XSS; null coalescing (??) ensures no
                    // undefined-array-key warnings if a column is unexpectedly missing.
                    $name        = htmlspecialchars($product['name'] ?? '', ENT_QUOTES, 'UTF-8');
                    $description = htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8');
                    // Images stored as e.g. "images/brown.jpg"; prepend ../ to resolve from php/ subdirectory
                    $image       = htmlspecialchars($product['image'] ?? '', ENT_QUOTES, 'UTF-8');
                    $category    = htmlspecialchars($product['category'] ?? '', ENT_QUOTES, 'UTF-8');
                    $price       = number_format((float)($product['price'] ?? 0), 2);
                    $id          = (int)($product['id'] ?? 0);
                ?>
                <div class="product-card"
                     data-id="<?= $id ?>"
                     data-name="<?= $name ?>"
                     data-category="<?= $category ?>">
                    <div class="img-container">
                        <img src="../<?= $image ?>" alt="<?= $name ?>">
                    </div>
                    <h3><?= $name ?></h3>
                    <p class="description"><?= $description ?></p>
                    <div class="price"><?= $price ?> JD</div>
                    <div class="quantity-area">
                        <label>Number of items:</label>
                        <input type="number" name="quantity[<?= $id ?>]" min="0" value="0">
                    </div>
                    <button class="add-to-cart-btn" data-id="<?= $id ?>">Add to Cart</button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>

        <div class="buy-button-container">
            <button type="submit" class="press-to-buy-btn">Press to Buy</button>
        </div>

    </form>

    <div class="shop-footer">
        <p>&copy; 2026 Adenor Perfumes.</p>
    </div>

    <!-- Embed product data as JSON so the client-side JS can use it for cart/filter operations -->
    <!-- Image paths in SHOP_DATA are kept as-is (e.g. "images/brown.jpg") because other pages like
         cart.html (in the root) need the root-relative path. -->
    <script>
        window.SHOP_DATA = <?= json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>

    <script src="../js/cart.js"></script>
    <script src="../js/shop.js"></script>
</body>
</html>
