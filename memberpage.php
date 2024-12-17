<?php
session_start();

include("connection.php");
$error_message = ""; // 初始化錯誤訊息

// 檢查用戶是否已登入
if(!isset($_SESSION['member_id'])) {
    // 如果未登入，重定向到登入頁面
    header("Location: login.php");
    die;
}

$member_id = $_SESSION['member_id'];
// 處理表單提交
if($_SERVER['REQUEST_METHOD'] == "POST") {
    // 從表單獲取數據，並進行基本的驗證
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // 基本驗證
    if(!empty($name) && !empty($email) && !empty($phone)) {
        // 檢查 email 格式
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "<script>alert('請輸入有效的電子郵件地址！');</script>";
        } else {
            // 如果用戶想更改密碼
            if(!empty($new_password)) {
                if($new_password !== $confirm_password) {
                    echo "<script>alert('新密碼與確認密碼不一致！');</script>";
                } else {
                    // 先驗證現有密碼
                    $query = "SELECT password FROM member WHERE member_id = ?";
                    $stmt = $con->prepare($query);
                    $stmt->bind_param("s", $member_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        if(password_verify($password, $row['password'])) {
                            // 現有密碼正確，更新資料包括新密碼
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            $update_query = "UPDATE member SET name = ?, email = ?, phone = ?, password = ? WHERE member_id = ?";
                            $update_stmt = $con->prepare($update_query);
                            $update_stmt->bind_param("sssss", $name, $email, $phone, $hashed_password, $member_id);
                            if($update_stmt->execute()) {
                                echo "<script>alert('資料更新成功！');</script>";
                            } else {
                                echo "<script>alert('更新失敗，請稍後再試。');</script>";
                            }
                        } else {
                            echo "<script>alert('現有密碼不正確！');</script>";
                        }
                    } else {
                        echo "<script>alert('會員資料未找到！');</script>";
                    }
                }
            } else {
                // 不更改密碼，只更新其他資料
                $update_query = "UPDATE member SET name = ?, email = ?, phone = ? WHERE member_id = ?";
                $update_stmt = $con->prepare($update_query);
                $update_stmt->bind_param("ssss", $name, $email, $phone, $member_id);
                if($update_stmt->execute()) {
                    echo "<script>alert('資料更新成功！');</script>";
                } else {
                    echo "<script>alert('更新失敗，請稍後再試。');</script>";
                }
            }
        }
    } else {
        echo "<script>alert('請填寫所有必要欄位！');</script>";
    }
}

// 獲取當前會員資料以顯示在表單中
$query = "SELECT name, email, phone FROM member WHERE member_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("s", $member_id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows > 0) {
    $member = $result->fetch_assoc();
} else {
    // 如果找不到會員資料，重定向到登入頁面
    header("Location: login.php");
    die;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>會員頁面</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: white; /* 背景淡藍色 */
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        #box {
            background-color: #85a3e0; /* 卡片淺藍色 */
            margin: auto;
            width: 400px;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        #box div {
            font-size: 24px;
            margin-bottom: 20px;
            color: #FFFFFF; /* 白色文字 */
            text-align: center;
            font-weight: bold;
        }

        #text {
            height: 30px;
            border-radius: 10px;
            padding: 5px;
            border: solid 2px #85a3e0; /* 淺藍邊框 */
            width: calc(100% - 14px);
            margin-bottom: 15px;
            font-size: 16px;
        }

        #button {
            padding: 10px;
            width: 100%;
            color: white;
            background-color: #4976d0; /* 深藍色按鈕 */
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 900; /* 更粗的按鈕文字 */
            cursor: pointer;
            transition: background-color 0.3s;
        }

        #button:hover {
            background-color: #85a3e0; /* 按鈕 hover 顏色 */
        }

        .message {
            color: yellow;
            margin-bottom: 10px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
        }

        a {
            color: #013B64; /* 更深的深藍色連結 */
            text-decoration: none;
            font-size: 14px;
            display: block;
            text-align: center;
            margin-top: 10px;
        }

        a:hover {
            text-decoration: underline;
        }

        label {
            font-weight: bold;
            margin-bottom: 5px;
            display: inline-block; /* 確保 margin-bottom 生效 */
            color: white; /* 標籤文字改為白色 */
        }
    </style>
</head>
<body>
    
    <div id="box">
        <form method="post">
            <div>會員資料</div>

            <?php
            if (!empty($message)) {
                echo "<div class='message'>$message</div>";
            }
            ?>

            <label>名字</label>
            <input id="text" type="text" name="name" value="<?php echo htmlspecialchars($member['name']); ?>">

            <label>電子郵件</label>
            <input id="text" type="text" name="email" value="<?php echo htmlspecialchars($member['email']); ?>">

            <label>電話</label>
            <input id="text" type="text" name="phone" value="<?php echo htmlspecialchars($member['phone']); ?>">

            <hr style="border: 1px solid white; margin: 20px 0;">

            <div style="font-size: 22px; margin: 20px 0; color: white;">更改密碼</div>

            <label>現有密碼</label>
            <input id="text" type="password" name="password">

            <label>新密碼</label>
            <input id="text" type="password" name="new_password">

            <label>確認新密碼</label>
            <input id="text" type="password" name="confirm_password">

            <input id="button" type="submit" value="更新">

            <div style="display: flex; justify-content: center; align-items: center; margin-top: 10px;">
                <a href="index.php" style="margin: 0 10px; text-decoration: none; color: #013B64;">回首頁</a>
                <span style="color: white;">|</span>
                <a href="logout.php" style="margin: 0 10px; text-decoration: none; color: #013B64;">登出</a>
            </div>
        </form>
    </div>
    <script>
        // 使用 PHP 傳遞的錯誤訊息
        const errorMessage = "<?php echo $error_message; ?>";
        if (errorMessage) {
            window.alert(errorMessage);
        }
    </script>
</body>
</html>

