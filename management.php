<?php
session_start();
include("connection.php");

// 檢查使用者是否登入
if (!isset($_SESSION['member_id'])) {
    header("Location: login.php");
    exit;
}

$member_id = $_SESSION['member_id'];
$isAdmin = false;

// 確認使用者是否有管理員權限
$query = "SELECT tier FROM admin WHERE member_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$member_id]);
$admin_data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin_data && $admin_data['tier'] >= 1) {
    $isAdmin = true;
}

if (!$isAdmin) {
    header("Location: index.php");
    exit;
}

// 處理新增、編輯、刪除請求
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == "add") {
        // 新增商品
        $name = $_POST['name'];
        $description = $_POST['description'];
        $category_id = $_POST['category_id'];
        $price = $_POST['price'];
        $quantity = $_POST['quantity'];
    
        // 檢查並處理圖片
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            // 獲取最大 product_id 並加 1
            $stmt = $pdo->query("SELECT MAX(product_id) AS max_id FROM product");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $product_id = $result['max_id'] + 1;
    
            // 設定圖片儲存的目錄及檔案名稱，使用 p_{product_id} 命名
            $image_dir = "image/"; // 修改為 'image/' 資料夾
            $image_name = "p_" . $product_id . ".jpg"; // 使用 p_{id} 命名
            $image_path = $image_dir . $image_name;
    
            // 移動上傳的檔案到指定目錄
            move_uploaded_file($_FILES["image"]["tmp_name"], $image_path);
        } else {
            $image_path = null;
        }
    
        // 插入新商品
        $stmt = $pdo->prepare("INSERT INTO product (product_id, category_id, name, description, image_path, quantity, price) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$product_id, $category_id, $name, $description, $image_path, $quantity, $price]);
    
        header("Location: management.php");
        exit;
    }
    

    if (isset($_POST['action']) && $_POST['action'] == "edit") {
        // 編輯商品
        if (isset($_POST['product_id'], $_POST['name'], $_POST['description'], $_POST['category_id'], $_POST['price'], $_POST['quantity'])) {
            $product_id = $_POST['product_id'];
            $name = $_POST['name'];
            $description = $_POST['description'];
            $category_id = $_POST['category_id'];
            $price = $_POST['price'];
            $quantity = $_POST['quantity'];
    
            // Process the product edit here (update query or other operations)
        } else {
            // Handle missing form data
            echo "Some required fields are missing.";
        }
    }

    if (isset($_POST['action']) && $_POST['action'] == "add_category") {
        // 新增商品類別
        if (isset($_POST['category_name'], $_POST['category_description'])) {
            $category_name = $_POST['category_name'];
            $category_description = $_POST['category_description'];
    
            // 檢查並處理類別圖片
            if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] == 0) {
                // 獲取最大 category_id 並加 1
                $stmt = $pdo->query("SELECT MAX(category_id) AS max_id FROM category");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $new_category_id = $result['max_id'] + 1;
    
                // 設定類別圖片儲存的目錄及檔案名稱，使用 c_{category_id} 命名
                $category_image_dir = "image/"; // 修改為 'image/' 資料夾
                $category_image_name = "c_" . $new_category_id . ".jpg"; // 使用 c_{id} 命名
                $category_image_path = $category_image_dir . $category_image_name;
    
                // 移動上傳的檔案到指定目錄
                move_uploaded_file($_FILES["category_image"]["tmp_name"], $category_image_path);
            } else {
                $category_image_path = null;
            }
    
            // 插入新類別
            $stmt = $pdo->prepare("INSERT INTO category (category_id, name, description, image_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$new_category_id, $category_name, $category_description, $category_image_path]);
    
            header("Location: management.php");
            exit;
        } else {
            echo "Category fields are missing.";
        }
    }
    
    

    if (isset($_POST['action']) && $_POST['action'] == "delete") {
        $product_id = $_POST['product_id'];
    
        try {
            // 開啟交易
            $pdo->beginTransaction();
    
            // 刪除 contains 表中與該產品相關的記錄
            $stmt = $pdo->prepare("DELETE FROM contains WHERE product_id = ?");
            $stmt->execute([$product_id]);
    
            // 刪除 product 表中的產品
            $stmt = $pdo->prepare("DELETE FROM product WHERE product_id = ?");
            $stmt->execute([$product_id]);
    
            // 提交交易
            $pdo->commit();
    
            header("Location: management.php");
            exit;
        } catch (PDOException $e) {
            // 發生錯誤回滾交易
            $pdo->rollBack();
            die("刪除失敗：" . $e->getMessage());
        }
    }
    

    if (isset($_POST['action']) && $_POST['action'] == "delete_category") {
        // 刪除類別
        $category_id = $_POST['category_id'];

        // 檢查類別是否有商品
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM product WHERE category_id = ?");
        $stmt->execute([$category_id]);
        $product_count = $stmt->fetchColumn();

        if ($product_count > 0) {
            // 如果該類別有商品，顯示錯誤訊息
            echo "該類別仍有商品，無法刪除。";
        } else {
            // 沒有商品，則刪除類別
            $stmt = $pdo->prepare("DELETE FROM category WHERE category_id = ?");
            $stmt->execute([$category_id]);
            header("Location: management.php");
            exit;
        }
    }
}

$categories = $pdo->query("SELECT * FROM category")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT p.*, c.name AS category_name FROM product p LEFT JOIN category c ON p.category_id = c.category_id")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品管理</title>
    <link href="style.css" rel="stylesheet">
    <style>
        /* 通用樣式 */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            padding-top: 50px;
            background-color: white;
            color: #333;
        }

        /*header {
            background-color: #85a3e0;
            color: white;
            padding: 10px 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            margin: 0;
            font-size: 24px;
        }

        header a {
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            margin-left: 20px;
        }

        header a:hover {
            text-decoration: underline;
        }*/

        main {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        h2 {
            color: #4976d0;
            margin-bottom: 10px;
            text-align:center;
        }

        form {
            background-color: white;
            margin: 0 auto;
            width:60%;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        form label {
            display: flex; /* 使用 Flexbox */
            align-items: center; /* 垂直置中 */
            margin-bottom: 10px;
        }

        form label input, 
        form label select, 
        form label textarea {
            flex-grow: 1; /* 輸入框自動占滿剩餘空間 */
            margin-left: 10px; /* 與文字間距 */
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: auto; /* 根據需要調整寬度 */
        }

        form button {
            background-color: #85a3e0;
            color: white;
            border: none;
            cursor: pointer;
        }

        form button:hover {
            background-color: #4976d0;
        }

        /* 表格樣式 */
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        table th, table td {
            text-align: center;
            padding: 10px;
            border: 1px solid #ddd;
        }

        table th {
            background-color: #f2f2f2;
            color: #333;
            font-weight: bold;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }

        img {
            max-width: 50px;
            height: auto;
        }

        button {
            padding: 5px 10px;
            width:80px;
            height:40px;
            font-size: 16px; /* 增大字體 */
            font-weight: bold; /* 文字粗體 */
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #4976d0;
            color: white;
        }
        .add {
            padding: 5px 10px;
            width:120px;
            height:40px;
            font-size: 16px; /* 增大字體 */
            font-weight: bold; /* 文字粗體 */
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .add:hover {
            background-color: #4976d0;
            color: white;
        }
        footer {
            text-align: center;
            background-color: #85a3e0;
            color: white;
            padding: 0px 0;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
        }
        header {
            background-color: #85a3e0;
            color: white;
            padding: 12px 20px;
            width: 100%; /* 確保滿版 */
            height: 70px;
            position: fixed; /* 固定在頁面頂部 */
            top: 0;
            left: 0;
            z-index: 1000; /* 確保在其他元素上方 */
            display: flex; /* 使用 flexbox 布局 */
            justify-content: space-between; /* 左右分布 */
            align-items: center; /* 垂直居中 */
            box-sizing: border-box; /* 包含 padding */
        }

        .header-links {
            display: flex; /* 設定水平排列 */
            gap: 15px; /* 圖標間距 */
            max-width: 100%; /* 限制寬度以避免超出畫面 */
            overflow: hidden; /* 防止溢出 */
            flex-wrap: wrap; /* 若空間不足則換行 */
        }

        .header-links a img {
            width: 35px; /* 圖標寬度 */
            height: 35px; /* 圖標高度 */
            object-fit: contain; /* 確保圖標比例 */
            cursor: pointer; /* 鼠標樣式 */
            transition: transform 0.3s; /* 動態效果 */
        }

        .header-links a img:hover {
            transform: scale(1.1); /* 鼠标悬停放大效果 */
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
            <?php if ($isAdmin): ?>
                <a href="orderpage.php">
                    <img src="order.png" alt="Order" title="Order">
                </a>
            <?php endif; ?>
        </div>
    </header>
<main>
    <h1>商品管理</h1>
    <h2>新增商品</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <!-- Product Fields -->
        <label>名稱: <input type="text" name="name" required></label>
        <label>描述: <input type="text" name="description"></label>
        <label>分類: 
            <select name="category_id">
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>價格: <input type="number" name="price" step="0.01" required></label>
        <label>數量: <input type="number" name="quantity" required></label>
        <label>圖片: <input type="file" name="image"></label>
        <button type="submit" class="add">新增商品</button>
    </form>

    <h2>新增商品類別</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_category">
        <label>類別名稱: <input type="text" name="category_name" required></label>
        <label>類別描述: <input type="text" name="category_description"></label>
        <label>類別圖片: <input type="file" name="category_image"></label>
        <button type="submit" class="add">新增類別</button>
    </form>

    <h2>商品類別列表</h2><br>
    <table border="1">
        <thead>
        <tr>
            <th>類別名稱</th>
            <th>描述</th>
            <th>圖片</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($categories as $category): ?>
            <tr>
                <td><?php echo htmlspecialchars($category['name']); ?></td>
                <td><?php echo htmlspecialchars($category['description']); ?></td>
                <td><img src="<?php echo htmlspecialchars($category['image_path']); ?>" alt="類別圖片" width="50"></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                        <button type="submit" onclick="return confirm('確認刪除類別？');">刪除</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table><br><br>

    <h2>商品列表</h2><br>
    <table border="1">
        <thead>
        <tr>
            <th>ID</th>
            <th>名稱</th>
            <th>描述</th>
            <th>分類</th>
            <th>價格</th>
            <th>數量</th>
            <th>圖片</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $product): ?>
            <tr>
                <td><?php echo htmlspecialchars($product['product_id']); ?></td>
                <td><?php echo htmlspecialchars($product['name']); ?></td>
                <td><?php echo htmlspecialchars($product['description']); ?></td>
                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                <td><?php echo htmlspecialchars($product['price']); ?></td>
                <td><?php echo htmlspecialchars($product['quantity']); ?></td>
                <td><img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="商品圖片" width="50"></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                        <button type="submit">編輯</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                        <button type="submit" onclick="return confirm('確認刪除商品？');">刪除</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table><br><br>
</main>
<footer>
    <p>&copy; 2024 TaoBay</p>
</footer>
</body>
</html>
