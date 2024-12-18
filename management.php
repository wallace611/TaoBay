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
            $image_path = "uploads/" . basename($_FILES["image"]["name"]);
            move_uploaded_file($_FILES["image"]["tmp_name"], $image_path);
        } else {
            $image_path = null;
        }

        // 獲取最大 product_id 並加 1
        $stmt = $pdo->query("SELECT MAX(product_id) AS max_id FROM product");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $product_id = $result['max_id'] + 1;

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

    if ($_POST['action'] == "add_category") {
        if (isset($_POST['category_name'], $_POST['category_description'])) {
            $category_name = $_POST['category_name'];
            $category_description = $_POST['category_description'];
    
            // Check if category image exists
            if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] == 0) {
                $image_path = "uploads/categories/" . basename($_FILES["category_image"]["name"]);
                move_uploaded_file($_FILES["category_image"]["tmp_name"], $image_path);
            } else {
                $image_path = null;
            }
    
            // Get the maximum category_id from the database and increment it by 1
            $stmt = $pdo->query("SELECT MAX(category_id) AS max_id FROM category");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $new_category_id = $result['max_id'] + 1;
    
            // Insert the new category with the new category_id
            $stmt = $pdo->prepare("INSERT INTO category (category_id, name, description, image_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$new_category_id, $category_name, $category_description, $image_path]);
    
            header("Location: management.php");
            exit;
        } else {
            echo "Category fields are missing.";
        }
    }
    
    

    if (isset($_POST['action']) && $_POST['action'] == "delete") {
        // 刪除商品
        $product_id = $_POST['product_id'];
        $stmt = $pdo->prepare("DELETE FROM product WHERE product_id = ?");
        $stmt->execute([$product_id]);

        header("Location: management.php");
        exit;
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
</head>
<body>
<header>
    <h1>商品管理</h1>
    <a href="index.php">返回首頁</a>
</header>
<main>
    <h2>新增商品</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <!-- Product Fields -->
        <label>名稱: <input type="text" name="name" required></label><br>
        <label>描述: <input type="text" name="description"></label><br>
        <label>分類: 
            <select name="category_id">
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label><br>
        <label>價格: <input type="number" name="price" step="0.01" required></label><br>
        <label>數量: <input type="number" name="quantity" required></label><br>
        <label>圖片: <input type="file" name="image"></label><br>
        <button type="submit">新增</button>
    </form>

    <h2>新增商品類別</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_category">
        <label>類別名稱: <input type="text" name="category_name" required></label><br>
        <label>類別描述: <input type="text" name="category_description"></label><br>
        <label>類別圖片: <input type="file" name="category_image"></label><br>
        <button type="submit">新增類別</button>
    </form>

    <h2>商品類別列表</h2>
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
    </table>

    <h2>商品列表</h2>
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
    </table>
</main>
<footer>
    <p>&copy; 2024 TaoBay</p>
</footer>
</body>
</html>
