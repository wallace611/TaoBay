<?php
session_start();
<<<<<<< HEAD:product/product.php
include("../connection.php");
=======
include("connection.php");
$member_data = check_login($con);
$is_admin = is_admin($con, $member_data['member_id']);
>>>>>>> 00d6fb1b1727055cd59e286990a8b26f48f96f84:product.php

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

// 處理新增至購物車的請求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $member_id = $_SESSION['member_id'] ?? null; // 確保會員 ID 存在於 session 中
    if (!$member_id) {
        die("錯誤：未登入會員，無法新增至購物車。");
    }

    // 獲取數量
    $quantity = $_POST['quantity'] ?? 1; // 默認數量為 1

    // 檢查購物車是否已存在
    try {
        $cart_stmt = $pdo->prepare("SELECT cart_id FROM cart WHERE member_id = :member_id AND is_checkout = 0");
        $cart_stmt->execute(['member_id' => $member_id]);
        $cart = $cart_stmt->fetch(PDO::FETCH_ASSOC);

        // 如果購物車不存在，則創建一個新的購物車
        if (!$cart) {
            // 獲取當前最大的 cart_id
            $max_cart_id_stmt = $pdo->query("SELECT MAX(cart_id) AS max_id FROM cart");
            $max_cart_id = $max_cart_id_stmt->fetch(PDO::FETCH_ASSOC)['max_id'];
            $cart_id = $max_cart_id ? $max_cart_id + 1 : 1; // 如果沒有 cart_id，則從 1 開始

            $insert_cart_stmt = $pdo->prepare("INSERT INTO cart (cart_id, member_id) VALUES (:cart_id, :member_id)");
            $insert_cart_stmt->execute(['cart_id' => $cart_id, 'member_id' => $member_id]);
        } else {
            $cart_id = $cart['cart_id'];
        }

        // 檢查該商品是否已在購物車中
        $check_contains_stmt = $pdo->prepare("SELECT quantity FROM contains WHERE cart_id = :cart_id AND product_id = :product_id");
        $check_contains_stmt->execute(['cart_id' => $cart_id, 'product_id' => $product_id]);
        $existing_product = $check_contains_stmt->fetch(PDO::FETCH_ASSOC);

        // 確認目前購物車中的數量
        $current_quantity = $existing_product ? $existing_product['quantity'] : 0;

        // 計算總數量
        $total_quantity = $current_quantity + $quantity;

        // 檢查是否超過可用商品數量
        if ($total_quantity > $product['quantity']) {
            echo "<script>alert('錯誤：目前購物車已有 {$current_quantity} 件，欲加入 {$quantity} 件，總數量將超過可用商品數量 {$product['quantity']} 件。');</script>";
        } else {
            if ($existing_product) {
                // 如果商品已存在，更新數量
                $new_quantity = $existing_product['quantity'] + $quantity;
                $update_quantity_stmt = $pdo->prepare("UPDATE contains SET quantity = :quantity WHERE cart_id = :cart_id AND product_id = :product_id");
                $update_quantity_stmt->execute(['quantity' => $new_quantity, 'cart_id' => $cart_id, 'product_id' => $product_id]);
            } else {
                // 如果商品不存在，插入新記錄
                $insert_contains_stmt = $pdo->prepare("INSERT INTO contains (cart_id, product_id, quantity) VALUES (:cart_id, :product_id, :quantity)");
                $insert_contains_stmt->execute(['cart_id' => $cart_id, 'product_id' => $product_id, 'quantity' => $quantity]);
            }

            echo "<script>alert('商品已成功加入購物車！');</script>";
        }
    } catch (PDOException $e) {
        die("新增至購物車失敗：" . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品詳細資訊</title>
    <link href="style.css" rel="stylesheet">
    <style>
        /* General styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f8f8;
            color: #333;
        }

        a {
            text-decoration: none;
            color: #007BFF;
        }


        /* Product container styles */
        .product-container {
            width: 90%;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .product-container img {
            max-width: 100%;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .product-title {
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .product-description {
            margin-bottom: 10px;
            font-size: 1em;
            color: #555;
        }

        .product-quantity,
        .product-price {
            font-size: 1.2em;
            margin-bottom: 10px;
        }

        form {
            margin-top: 10px;
        }

        form input[type="number"] {
            width: 60px;
            padding: 5px;
            margin-right: 10px;
        }

        .add-to-cart-btn {
            display: inline-block;
            background-color: #4976d0;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .add-to-cart-btn:hover {
            background-color: #85a3e0;
        }

        /* Random products section */
        .random-products {
            margin: 20px auto;
            padding: 20px;
            width: 90%;
            max-width: 1200px;
        }

        .random-products-title {
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
        }
        /* 推薦商品的按鈕樣式 */
        .random-products .card-body a {
            display: inline-block;
            background-color: #4976d0; /* 按鈕背景色 */
            color: #fff; /* 按鈕文字顏色 */
            padding: 8px 16px;
            border-radius: 5px;
            text-align: center;
            font-size: 1em;
            transition: background-color 0.3s, color 0.3s;
            text-decoration: none;
        }

        .random-products .card-body a:hover {
            background-color: #85a3e0; /* 懸停時的背景色 */
            color: #e6e6e6; /* 懸停時的文字顏色 */
        }

        .container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        .card {
            width: 200px;
            background-color: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .card img {
            width: 100%;
            height: auto;
            border-bottom: 1px solid #ddd;
        }

        .card-body {
            padding: 15px;
        }

        .card-title {
            font-size: 1.1em;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .card-price {
            color: red;
            margin-bottom: 10px;
            font-size: 1.2em;
            font-weight: bold;
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
        footer {
            background-color: #85a3e0;
            color: white;
            text-align: center;
            padding: 0px 0;
            width: 100%; /* 確保滿版 */
            position: fixed; /* 貼合底部 */
            bottom: 0;
            left: 0;
        }
        header h1 {
            margin: 0;
        }
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
            <a href="index.php">
                <img src="home.png" alt="Home" title="Home">
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
    <div class="product-container">
        <img src="<?php echo htmlspecialchars('../' . ($product['image_path'] ?? 'images/default.png')); ?>" 
        alt="<?php echo htmlspecialchars($product['name']); ?>">
        <div class="product-title"><?php echo htmlspecialchars($product['name']); ?></div>
        <div class="product-description"><?php echo htmlspecialchars($product['description']); ?></div>
        <div class="product-quantity">數量：<?php echo htmlspecialchars($product['quantity']); ?></div>
        <div class="product-price">NTD <?php echo htmlspecialchars(number_format($product['price'], 2)); ?></div>
        
        <form method="POST" action="">
            <label for="quantity">數量:</label>
            <input type="number" name="quantity" id="quantity" value="1" min="1" required>
            <br>
            <br>
            <button type="submit" name="add_to_cart" class="add-to-cart-btn">新增至購物車</button>
        </form>
        <br>
        <a href="../index.php" class="add-to-cart-btn">返回主頁面</a>
        <a href="checkout3.php" class="add-to-cart-btn">查看購物車</a>
    </div>

    <div class="random-products">
        <div class="random-products-title">推薦商品</div>
        <div class="container">
            <?php foreach ($random_products as $random_product): ?>
                <div class="card">
                        <img src="<?php echo htmlspecialchars('../' . ($random_product['image_path'] ?? 'images/default.png')); ?>" 
                        alt="<?php echo htmlspecialchars($random_product['name']); ?>">                    
                    <div class="card-body">
                        <div class="card-title"><?php echo htmlspecialchars($random_product['name']); ?></div>
                        <div class="card-price">NTD <?php echo htmlspecialchars(number_format($random_product['price'], 2)); ?></div>
                        <a href="product.php?product_id=<?php echo htmlspecialchars($random_product['product_id']); ?>">查看商品</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <footer>
        <p>&copy; 2024 TaoBay</p>
    </footer>
</body>
</html>