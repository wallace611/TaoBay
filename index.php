<?php
// 資料庫連線資訊
$host = 'localhost'; // 主機
$dbname = 'database';  // 資料庫名稱
$username = 'root';  // 資料庫使用者名稱
$password = '';      // 資料庫密碼

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("資料庫連線失敗：" . $e->getMessage());
}

try {
    $stmt = $pdo->query("SELECT * FROM product");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("讀取資料失敗：" . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品列表</title>
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <h1>商品列表</h1>
    <div class="container">
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $category): ?>
                <div class="category">
                    <!-- 類別介紹照片 -->
                    <div class="category-header">
                        <img src="<?php echo htmlspecialchars($category['image']); ?>" 
                             alt="<?php echo htmlspecialchars($category['name']); ?>" 
                             class="category-image">
                        <h2><?php echo htmlspecialchars($category['name']); ?></h2>
                    </div>

                    <!-- 類別商品清單 -->
                    <div class="product-list">
                        <?php if (!empty($category['products'])): ?>
                            <?php foreach (array_slice($category['products'], 0, 4) as $product): ?>
                                <div class="card">
                                    <img src="https://via.placeholder.com/250x150.png?text=Product+Image" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <div class="card-body">
                                        <div class="card-title"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <div class="card-description"><?php echo htmlspecialchars($product['description']); ?></div>
                                        <div class="card-price">NTD <?php echo htmlspecialchars(number_format($product['price'], 2)); ?></div>
                                        <a href="product.php?product_id=<?php echo htmlspecialchars($product['product_id']); ?>">查看商品</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center;">此類別目前沒有商品。</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center;">目前沒有商品分類。</p>
        <?php endif; ?>
    </div>
</body>
</html>
