<?php 
session_start();

	include("connection.php");
	include("functions.php");

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