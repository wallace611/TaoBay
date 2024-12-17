<?php
include("connection.php");

// 取得商品 ID
$product_id = $_GET['product_id'] ?? null;
if (!$product_id) {
    die("未提供商品 ID");
}

// 查詢商品詳細資料
try {
    $stmt = $pdo->prepare("SELECT * FROM product WHERE product_id = :product_id");
    $stmt->execute(['product_id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        die("找不到對應的商品");
    }
} catch (PDOException $e) {
    die("讀取資料失敗：" . $e->getMessage());
}

// 查詢隨機商品（不包括當前商品）
try {
    $random_stmt = $pdo->prepare("SELECT * FROM product WHERE product_id != :product_id ORDER BY RAND() LIMIT 4");
    $random_stmt->execute(['product_id' => $product_id]);
    $random_products = $random_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("讀取隨機商品失敗：" . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品詳細資訊</title>
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="product-container">
        <img src="https://via.placeholder.com/600x300.png?text=Product+Image" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
        <div class="product-title"><?php echo htmlspecialchars($product['name']); ?></div>
        <div class="product-description"><?php echo htmlspecialchars($product['description']); ?></div>
        <div class="product-quantity">數量：<?php echo htmlspecialchars($product['quantity']); ?></div>
        <div class="product-price">NTD <?php echo htmlspecialchars(number_format($product['price'], 2)); ?></div>
        <a href="#" class="add-to-cart-btn">新增至購物車</a>
        <br><br>
        <a href="index.php" class="add-to-cart-btn" style="background-color: #2ecc71;">返回商品列表</a>
    </div>

    <div class="random-products">
        <div class="random-products-title">推薦商品</div>
        <div class="container">
            <?php foreach ($random_products as $random_product): ?>
                <div class="card">
                    <img src="https://via.placeholder.com/250x150.png?text=Product+Image" alt="<?php echo htmlspecialchars($random_product['name']); ?>">
                    <div class="card-body">
                        <div class="card-title"><?php echo htmlspecialchars($random_product['name']); ?></div>
                        <div class="card-price">NTD <?php echo htmlspecialchars(number_format($random_product['price'], 2)); ?></div>
                        <a href="product.php?product_id=<?php echo htmlspecialchars($random_product['product_id']); ?>">查看商品</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
