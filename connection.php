<?php

$dbhost = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "taobay";

function check_login($con)
{
    if (isset($_SESSION['member_id'])) {
        $id = $_SESSION['member_id'];
        $query = "SELECT * FROM member WHERE member_id = '$id' LIMIT 1";
        $result = mysqli_query($con, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $member_data = mysqli_fetch_assoc($result);
            return $member_data;
        }
    }

    // Redirect to login
    header("Location: login.php");
    die;
}

function is_admin($con, $member_id) {
    // Check if the user is in the admin table and has tier >= 1
    $query = "SELECT tier FROM admin WHERE member_id = '$member_id' AND tier >= 1";
    $result = mysqli_query($con, $query);
    return $result && mysqli_num_rows($result) > 0;
}

if(!$con = mysqli_connect($dbhost,$dbuser,$dbpass,$dbname))
{

	die("failed to connect!");
}

try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("資料庫連線失敗：" . $e->getMessage());
}

?>