<?php
require_once("./inc/bot.php");

//If no valid token, then they better be authorized
if(!isset($_GET["token"]) || (isset($_GET["token"]) && $_GET["token"] != $config["bot"]["validation_key"])){
	if(!is_authorized()){
		http_response_code(401);
		$msg = array(
			"result" => "error",
			"reason" => "Key Error",
			"details" => "Invalid Key or Key Not Provided");
		die(json_encode($msg));
	}
}

$db = new Database($config["db"]["db_host"],
	$config["db"]["db_user"],
	$config["db"]["db_pass"],
	$config["db"]["db_name"]);

if(!$db->connect()){
	http_response_code(400);
	$msg = array(
		"result" => "error",
		"reason" => "DB Error",
		"details" => "DB Connection Error");
	die(json_encode($msg));
}

$cur_vote = $db->current_vote();

if($cur_vote == 0){
	http_response_code(400);
	$msg = array(
		"result" => "error",
		"reason" => "Vote Error",
		"details" => "No In-Progress Vote");
	die(json_encode($msg));
}

switch($_GET["task"]){
	case "list_candidates":
		//if vote in progress, and http method = get, get the candidates
		$candidates = $db->get_candidates($cur_vote, true);
		http_response_code(200);
			$msg = array(
				"result" => "success",
				"data" => $candidates);
			die(json_encode($msg));
		break;
	case "cast_ballot":
		if(!isset($_GET["ballot"])){
			http_response_code(400);
			$msg = array(
				"result" => "error",
				"reason" => "Ballot Error",
				"details" => "No Ballot. Please provide a ballot number to vote.");
			die(json_encode($msg));
		}
		$result = $db->cast_ballot($_GET["ballot"]);
		if($result){
			$msg = array("result" => "success");
		}else{
			http_response_code(500);
			$msg = array("result" => "error",
				"reason" => "INSERT error");
		}
		die(json_encode($msg));
		break;
	case "monitor":
		$votes = $db->get_votes($cur_vote);
		if($votes){
			$msg = array("result" => "success",
				"data" => $votes);
		}else{
			http_response_code(500);
			$msg = array("result" => "error",
				"reason" => "Error getting vote count.");
		}
		die(json_encode($msg));
		break;
	default:
		http_response_code(400);
		$msg = array(
			"result" => "error",
			"reason" => "Task Error",
			"details" => "Invalid Task. Valid Tasks: start, end, vote");
		die(json_encode($msg));
}



?>