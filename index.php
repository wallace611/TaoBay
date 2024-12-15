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

// 讀取分類和商品
try {
    // 查詢所有分類
    $categoryStmt = $pdo->query("SELECT * FROM category");
    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

    // 查詢每個分類的商品
    foreach ($categories as &$category) {
        $categoryId = $category['category_id'];
        $productStmt = $pdo->prepare("SELECT * FROM product WHERE category_id = ?");
        $productStmt->execute([$categoryId]);
        $category['products'] = $productStmt->fetchAll(PDO::FETCH_ASSOC);
    }
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box; /* 確保所有元素不會多出邊框或間距 */
        }

        body, html {
            width: 100%;
            margin: 0;
        }

        .category-header {
            text-align: center; /* 置中文字與內容 */
            margin: 0 auto; /* 水平置中 */
            max-width: 100%; /* 確保不超出父容器寬度 */
            overflow: hidden; /* 防止超出內容 */
        }

        .category-header img {
            width: 1296px; /* 保持圖片原始寬度 */
            height: 648px; /* 保持圖片原始高度 */
            max-width: 80%; /* 確保圖片在小螢幕上不超出視窗寬度 */
            height: auto; /* 保持圖片比例縮放 */
            display: block; /* 去除圖片下方的空白間隙 */
            margin: 10px auto; /* 加入垂直間距並置中 */
        }

        .category-header h2 {
            font-size: 24px;
            margin-bottom: 10px; /* 與圖片間距 */
            color: #333; /* 調整文字顏色 */
        }

        .product-list {
            display: flex; /* 使用彈性盒子排列商品 */
            flex-wrap: wrap; /* 超出時自動換行 */
            justify-content: flex-start; /* 商品與左邊對齊 */
            gap: 20px; /* 卡片之間的間距 */
            margin-top: 20px; /* 與類別圖片保持適當間距 */
            padding-left: 0; /* 確保與父容器左對齊 */
        }

        .card {
            flex: 0 1 calc(25% - 20px); /* 每行最多顯示4張卡片，間距20px */
            box-sizing: border-box; /* 確保邊框與內容一起計算寬度 */
            margin: 0; /* 清除多餘的外距 */
            text-align: center; /* 卡片內容置中 */
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden; /* 防止內容溢出 */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card img {
            width: 100%;
            height: 150px; /* 固定圖片高度 */
            object-fit: cover; /* 確保圖片比例裁剪合理 */
        }

        .card-body {
            padding: 10px;
        }

        .card-title {
            font-size: 18px;
            font-weight: bold;
            margin: 5px 0;
        }

        .card-description {
            font-size: 14px;
            color: #555;
            margin: 5px 0 10px 0;
        }

        .card-price {
            font-size: 16px;
            color: #e63946;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .container {
            width: 90%;
            max-width: 1200px; /* 限制最大寬度 */
            margin: 0 auto; /* 置中 */
            padding: 20px 0; /* 上下間距 */
        }
        .category {
            margin-bottom: 40px; /* 每個類別區塊的間距 */
        }

    </style>
</head>
<body>
    <h1>商品列表</h1>
    <div class="container">
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $category): ?>
                <div class="category">
                    <div class="category-header">
                        <h2><?php echo htmlspecialchars($category['name']); ?></h2>
                        <img src="<?php echo htmlspecialchars($category['image_path'] ?? 'https://via.placeholder.com/250x150.png?text=No+Image'); ?>" 
                             alt="<?php echo htmlspecialchars($category['name']); ?>">
                    </div>
                    <div class="product-list">
                        <?php if (!empty($category['products'])): ?>
                            <?php foreach (array_slice($category['products'], 0, 4) as $product): ?>
                                <div class="card">
                                    <img src="<?php echo htmlspecialchars($product['image_path'] ?? 'https://via.placeholder.com/250x150.png?text=Product+Image'); ?>" 
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

