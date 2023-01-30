<?php
require_once("./inc/bot.php");

//If user is not an authorized user, redirect to index
redirect_unauthorized();

vote

//Create DB Connection
$db = new Database($config["db"]["db_host"],
	$config["db"]["db_user"],
	$config["db"]["db_pass"],
	$config["db"]["db_name"]);

if(!$db->connect()){
	//To-Do: show proper error msg
	redirect_error("dbconnecterror");
}

