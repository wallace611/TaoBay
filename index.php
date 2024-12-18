<?php
session_start();
include("connection.php");

$member_data = check_login($con);

// Check if the current user is an admin with tier >= 1
$is_admin = is_admin($con, $member_data['member_id']);

// Load categories and products
try {
    // Query categories
    $categoryStmt = $pdo->query("SELECT * FROM category");
    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
    // Query products for each category
    foreach ($categories as &$category) {
        $categoryId = $category['category_id'];
        $productStmt = $pdo->prepare("SELECT * FROM product WHERE category_id = ?");
        $productStmt->execute([$categoryId]);
        $category['products'] = $productStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("讀取資料失敗：" . $e->getMessage());
}
// Fetch all categories and their associated products
$query = "
    SELECT c.*, p.* 
    FROM category c
    LEFT JOIN product p ON c.category_id = p.category_id
    ORDER BY c.category_id
";
$stmt = $pdo->query($query);
//$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize products by category
$categoryProducts = [];
foreach ($categories as &$category) {
    $categoryId = $category['category_id'];
    $categoryProducts[$categoryId]['name'] = $category['name'];
    $categoryProducts[$categoryId]['image_path'] = $category['image_path'];

    // 檢查是否存在產品
    if (!empty($category['products']) && is_array($category['products'])) {
        foreach ($category['products'] as $product) {
            $categoryProducts[$categoryId]['products'][] = [
                'product_id' => $product['product_id'],
                'name' => $product['name'],
                'description' => $product['description'],
                'price' => $product['price'],
                'image_path' => $product['image_path']
            ];
        }
    } else {
        // 若沒有產品，則初始化為空陣列
        $categoryProducts[$categoryId]['products'] = [];
    }
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

        html, body {
            margin: 0; /* 移除任何預設邊距 */
            padding: 0; /* 移除任何預設內距 */
            width: 100%;
            height: 100%;
        }

        header {
            background-color: #85a3e0;
            color: white;
            padding: 17px 20px;
            display: flex; /* 使用 flexbox 布局 */
            justify-content: space-between; /* 左右分布 */
            align-items: center; /* 垂直居中 */
        }

        .header-links {
            display: flex; /* 设置水平排列 */
            gap: 15px; /* 图标之间的间距 */
        }

        .header-links a img {
            width: 35px; /* 图标宽度 */
            height: 35px; /* 图标高度 */
            object-fit: contain; /* 确保图标比例 */
            cursor: pointer; /* 鼠标悬停显示手型 */
            transition: transform 0.3s; /* 添加动态效果 */
        }

        .header-links a img:hover {
            transform: scale(1.1); /* 鼠标悬停放大效果 */
        }
        .welcome-message {
            font-size: 16px;
            position: absolute; /* 绝对定位 */
            right: 20px; /* 靠右距离 */
            top: 80px; /* 靠上距离 */
            white-space: nowrap; /* 防止换行 */
        }

        footer {
            background-color: #85a3e0;
            color: white;
            text-align: center;
            padding: 12px 0;
            width: 100%; /* 確保滿版 */
            position: fixed; /* 貼合底部 */
            bottom: 0;
            left: 0;
        }
        header h1 {
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

        .product-list {
            display: flex;
            flex-wrap: wrap; /* 超出時自動換行 */
            justify-content: center; /* 對齊到中間 */
            gap: 20px; /* 卡片之間的間距 */
            margin-top: 20px; /* 與類別圖片保持適當間距 */
            padding-left: 0; /* 確保與父容器左對齊 */
            max-width: 1296px; /* 與圖片寬度一致 */
            margin-left: auto;
            margin-right: auto; /* 水平置中 */
        }

        .card {
            flex: 0 0 calc(25% - 20px); /* 4 欄，每個佔 25%，考慮間距 */
            max-width: 250px; /* 保持卡片的固定大小 */
            margin: 0;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card img {
            width: 160px; /* 固定寬度 */
            height: 160px; /* 固定高度 */
            object-fit: cover; /* 確保圖片比例裁剪合理 */
            display: block; /* 去除下方空隙 */
            margin: 0 auto; /* 居中圖片 */
        }

        .card-body {
            padding: 10px;
        }
        .card-body a {
            display: inline-block;
            background-color: #4976d0; /* 按鈕背景色 */
            color: #fff; /* 按鈕文字顏色 */
            padding: 8px 16px;
            border-radius: 5px;
            text-align: center;
            font-size: 1em;
            transition: background-color 0.3s, color 0.3s;
        }

        .card-body a:hover {
            background-color: #85a3e0; /* 懸停時的背景色 */
            color: #e6e6e6; /* 懸停時的文字顏色 */
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
            max-width: 1296px; /* 與圖片寬度保持一致 */
            margin: 0 auto;
            padding: 20px 0;
        }
        .category {
            margin-bottom: 40px; /* 每個類別區塊的間距 */
        }
        /*footer {
            background-color: #85a3e0;
            color: white;
            text-align: center;
            padding: 3px 0;
            position: fixed;
            bottom: 0;
            width: 100%;
        }*/

    </style>
</head>
<body>
<header>
    <h1>TaoBay</h1>
    <div class="header-links">
        <a href="checkout3.php">
            <img src="cart.png" alt="Shopping Cart" title="Shopping Cart">
        </a>
        <a href="memberpage.php">
            <img src="person.png" alt="Member Page" title="Member Page">
        </a>
        <a href="logout.php">
            <img src="logout.png" alt="Logout" title="Logout">
        </a>
        <?php if ($is_admin): ?>
            <a href="management.php">
                <img src="manage.png" alt="Manage" title="Manage">
            </a>
            <a href="orderpage.php">
                <img src="order.png" alt="Order" title="Order">
            </a>
        <?php endif; ?>
    </div>
</header>

	
	<br>
	<div class="welcome-message">
        Hello, <?php echo htmlspecialchars($member_data['name']); ?>
    </div>
    <h1>商品列表</h1>
    <div class="container">
    <?php if (!empty($categoryProducts)): ?>
        <?php foreach ($categoryProducts as $category_id => $category_data): ?>
            <div class="category">
                <div class="category-header">
                    <img src="<?php echo htmlspecialchars($category_data['image_path'] ?? 'https://via.placeholder.com/250x150.png?text=No+Image'); ?>" 
                         alt="<?php echo htmlspecialchars($category_data['name']); ?>">
                </div>
                <div class="product-list">
                    <?php if (!empty($category_data['products'])): ?>
                        <?php foreach (array_slice($category_data['products'], 0, 4) as $product): ?>
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

    <footer>
        <p>&copy; 2024 TaoBay</p>
    </footer>

</body>
</html>
