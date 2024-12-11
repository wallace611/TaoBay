<?php
// 資料庫連線資訊
$host = 'localhost';
$dbname = 'taobay';
$username = 'root';
$password = '';

// 建立資料庫連線
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("資料庫連線失敗：" . $e->getMessage());
}

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
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
        }
        .product-container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .product-image {
            width: 100%;
            height: auto;
        }
        .product-title {
            font-size: 24px;
            margin: 20px 0;
        }
        .product-description {
            font-size: 16px;
            color: #555;
            margin-bottom: 10px;
        }
        .product-quantity {
            font-size: 16px;
            color: #2ecc71;
            margin-bottom: 10px;
        }
        .product-price {
            font-size: 20px;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 20px;
        }
        .add-to-cart-btn {
            display: inline-block;
            text-decoration: none;
            color: #fff;
            background-color: #3498db;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        .add-to-cart-btn:hover {
            background-color: #2980b9;
        }
        .random-products {
            margin-top: 50px;
            text-align: center;
        }
        .random-products-title {
            font-size: 20px;
            margin-bottom: 20px;
        }
        .container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }
        .card {
            width: 250px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        .card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .card-body {
            padding: 15px;
        }
        .card-title {
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
        }
        .card-price {
            font-size: 16px;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 15px;
        }
        .card a {
            display: inline-block;
            text-decoration: none;
            color: #fff;
            background-color: #3498db;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        .card a:hover {
            background-color: #2980b9;
        }
    </style>
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
