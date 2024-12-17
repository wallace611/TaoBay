<?php

function check_login($con)
{

	if(isset($_SESSION['member_id']))
	{

		$id = $_SESSION['member_id'];
		$query = "select * from member where member_id = '$id' limit 1";

		$result = mysqli_query($con,$query);
		if($result && mysqli_num_rows($result) > 0)
		{

			$member_data = mysqli_fetch_assoc($result);
			return $member_data;
		}
	}

	//redirect to login
	header("Location: login.php");
	die;

}
?>

<?php 
session_start();

	include("connection.php");

	$member_data = check_login($con);

?>

<!DOCTYPE html>
<html>
<head>
	<title>My website</title>
</head>
<body>

	<a href="logout.php">Logout</a>
	<a href="memberpage.php">Member Page</a>
	<h1>This is the index page</h1>
	

	<br>
	Hello, <?php echo $member_data['name']; ?>
</body>
</html>