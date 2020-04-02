<?php
	session_start();
	if (!file_exists("chat"))
		file_put_contents("chat", json_encode(array()));
	$data = json_decode(file_get_contents("chat"));

	$tmp['name'] = $_SESSION['name'];
	$tmp['msg'] = $_GET['msg'];
	$tmp['date'] = date("U");
	$data[] = $tmp;

	$chat = file_put_contents("chat", json_encode($data));
	
?>