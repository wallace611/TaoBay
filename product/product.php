<?php
session_start();
include("../connection.php");

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
        .product-container img {
            width: 250px; /* 固定圖片寬度 */
            height: 250px; /* 固定圖片高度 */
            object-fit: cover; /* 確保圖片比例正常，不變形 */
            border: 1px solid #ddd; /* 可選：增加邊框 */
            border-radius: 5px; /* 可選：讓圖片有圓角 */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* 可選：增加陰影效果 */
            display: block;
            margin: 0 auto; /* 居中 */
        }

        .product-container {
            text-align: center; /* 圖片與文字居中 */
            margin: 20px auto; /* 增加外邊距 */
            max-width: 600px; /* 限制容器寬度 */
        }

        .product-container .product-title {
            font-size: 24px;
            font-weight: bold;
            margin-top: 10px;
        }

        .product-container .product-description,
        .product-container .product-quantity,
        .product-container .product-price {
            margin: 10px 0;
        }

        .random-products .card img {
            width: 200px; /* 固定寬度 */
            height: 200px; /* 固定高度 */
            object-fit: cover; /* 確保圖片比例正常，不變形 */
        }
    </style>

</head>
<body>
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
</body>
</html>