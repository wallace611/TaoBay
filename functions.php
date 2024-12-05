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

function random_num($length)
{

	$text = "";
	if($length < 5)
	{
		$length = 5;
	}

	$len = rand(4,$length);

	for ($i=0; $i < $len; $i++) { 
		# code...

		$text .= rand(0,9);
	}

	return $text;
}