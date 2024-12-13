<?php
session_start();

include("connection.php");
include("functions.php");

// 檢查用戶是否已登入
if(!isset($_SESSION['member_id'])) {
    // 如果未登入，重定向到登入頁面
    header("Location: login.php");
    die;
}

$member_id = $_SESSION['member_id'];
$message = "";

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
            $message = "請輸入有效的電子郵件地址！";
        } else {
            // 如果用戶想更改密碼
            if(!empty($new_password)) {
                if($new_password !== $confirm_password) {
                    $message = "新密碼與確認密碼不一致！";
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
                                $message = "資料更新成功！";
                            } else {
                                $message = "更新失敗，請稍後再試。";
                            }
                        } else {
                            $message = "現有密碼不正確！";
                        }
                    } else {
                        $message = "會員資料未找到！";
                    }
                }
            } else {
                // 不更改密碼，只更新其他資料
                $update_query = "UPDATE member SET name = ?, email = ?, phone = ? WHERE member_id = ?";
                $update_stmt = $con->prepare($update_query);
                $update_stmt->bind_param("ssss", $name, $email, $phone, $member_id);
                if($update_stmt->execute()) {
                    $message = "資料更新成功！";
                } else {
                    $message = "更新失敗，請稍後再試。";
                }
            }
        }
    } else {
        $message = "請填寫所有必要欄位！";
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
    <style type="text/css">
        #text {
            height: 25px;
            border-radius: 5px;
            padding: 4px;
            border: solid thin #aaa;
            width: 100%;
        }

        #button {
            padding: 10px;
            width: 100px;
            color: white;
            background-color: lightblue;
            border: none;
            cursor: pointer;
        }

        #box {
            background-color: grey;
            margin: auto;
            width: 400px;
            padding: 20px;
            border-radius: 10px;
        }

        .message {
            color: yellow;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

    <div id="box">
        <form method="post">
            <div style="font-size: 20px; margin: 10px; color: white;">會員資料</div>

            <?php
            if(!empty($message)) {
                echo "<div class='message'>$message</div>";
            }
            ?>

            <label>名字</label><br>
            <input id="text" type="text" name="name" value="<?php echo htmlspecialchars($member['name']); ?>"><br><br>

            <label>電子郵件</label><br>
            <input id="text" type="text" name="email" value="<?php echo htmlspecialchars($member['email']); ?>"><br><br>

            <label>電話</label><br>
            <input id="text" type="text" name="phone" value="<?php echo htmlspecialchars($member['phone']); ?>"><br><br>

            <hr>

            <div style="font-size: 16px; margin: 10px; color: white;">更改密碼</div>

            <label>現有密碼</label><br>
            <input id="text" type="password" name="password"><br><br>

            <label>新密碼</label><br>
            <input id="text" type="password" name="new_password"><br><br>

            <label>確認新密碼</label><br>
            <input id="text" type="password" name="confirm_password"><br><br>

            <input id="button" type="submit" value="更新"><br><br>

            <a href="index.php">回首頁</a> | <a href="logout.php">登出</a>
        </form>
    </div>
</body>
</html>
