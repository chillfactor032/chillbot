<?php
require_once("./inc/bot.php");

//If user is not an authorized user, redirect to index
redirect_unauthorized();

//Create DB Connection
$db = new Database($config["database"]);

if(!$db->connect()){
	//To-Do: show proper error msg
	redirect_error("dbconnecterror");
}

?>