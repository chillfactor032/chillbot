<?php
session_start();

require_once("./inc/twitch.php");
require_once("./inc/config.php");
require_once("./inc/db.php");
require_once("./inc/log.php");

$POOL_ROOM_BALLOT = 9999;

$links = [
	[
		"name" => "Dash",
		"file" => "index.php",
        "icon" => "fa-solar-panel",
		"highlight" => ""
	],[
		"name" => "Voting",
		"file" => "livelearns.php",
        "icon" => "fa-check-to-slot",
		"highlight" => ""
	],[
		"name" => "Raids",
		"file" => "raids.php",
        "icon" => "fa-users-line",
		"highlight" => ""
	],[
		"name" => "Chat",
		"file" => "chat.php",
        "icon" => "fa-comment",
		"highlight" => ""
	],[
		"name" => "Logs",
		"file" => "logs.php",
        "icon" => "fa-file-lines",
		"highlight" => ""
	]
];

$bot_name = $config["bot_name"];

$twitch = new Twitch($config["twitch"]["client_id"],$config["twitch"]["client_secret"],$config["twitch"]["redirect_url"]);

if(isset($_SESSION['twitch_state']) && !empty($_SESSION['twitch_state'])){
	$state = $_SESSION["twitch_state"];
}else{
	$state = $twitch->get_state();
	$_SESSION["twitch_state"] = $state;
}

function get_user_info(){
	global $config;
	$user_info = array(
		"user_name" => "",
		"pic_url" => "",
		"logged_in" => false, 
		"authorized" => false
	);
	if($config["env"] == "test"){
		$user_info["logged_in"] = true;
		$user_info["pic_url"] = "img/squirrel.jpg";
		$user_info["user_name"] = "DEV_ENV";
		$user_info["authorized"] = true;
		return $user_info;
	}
	if(isset($_SESSION["twitch_user"]["display_name"]) && isset($_SESSION["twitch_user"]["profile_image_url"])){
		$user_info["logged_in"] = true;
		$user_info["pic_url"] = $_SESSION["twitch_user"]["profile_image_url"];
		$user_info["user_name"] = $_SESSION["twitch_user"]["display_name"];
		if(in_array($_SESSION["twitch_user"]["login"], $config["twitch"]["authorized_users"])){
			$user_info["authorized"] = true;
		}
	}
	return $user_info;
}

//Conventience function to get the authorized flag
function is_authorized(){
	$user_info = get_user_info();
	return $user_info["authorized"];
}

function page_header($cur_file){
	$html = <<<HTML
<div class="header">
	MrFusionBot
</div>
HTML;
	echo($html);
	page_logged_in();
	page_navbar($cur_file);
}

function page_logged_in(){
	$user_info = get_user_info();
	$html = "";

	if($user_info["logged_in"]){
		$user_escaped = htmlentities($user_info["user_name"]);
		$user_url_enc = urlencode($user_info["user_name"]);
		$pic = $user_info["pic_url"];
		$html = <<<EOD
<div class="welcome">
	<span style="vertical-align: middle;"> 
		$user_escaped &nbsp;
	</span>
	<img class="image-cropper" src="$pic"> 
</div>
EOD;
		return $html;
	}
    return "";
}

function sidenav($cur_file){
    global $bot_name, $links, $twitch, $state;
    $cur_file = basename($cur_file);
	for($i = 0; $i < count($links); $i++){
		if($cur_file == $links[$i]["file"]){
			$links[$i]["highlight"] = "active";
		}
	}
    $user_info = get_user_info();
    $page_title = page_title($cur_file);
    $random = rand();
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="bot.css?$random">
<link rel="stylesheet" type="text/css" href="toastify.css">
<script type="text/javascript" src="toastify.js"></script>
<script src="https://kit.fontawesome.com/943ccd817c.js" crossorigin="anonymous"></script>
<script type="text/javascript" src="bot.js?$random"></script>
<title>$page_title</title>
</head>
<body>
HTML;

	$html .= "<div class=\"sidenav\">\r\n";
	for($i = 0; $i < count($links); $i++){
		if($cur_file == $links[$i]["file"]){
			$links[$i]["highlight"] = "active";
			$html .= "\t<a href=\"".$links[$i]["file"]."\"><p><i class=\"fa-solid ".$links[$i]["icon"]. " icon\"></i>".$links[$i]["name"]."&nbsp;<i class=\"fa-solid fa-caret-right\"></i></p></a>\r\n";
		}else{
			$html .= "\t<a href=\"".$links[$i]["file"]."\"><p><i class=\"fa-solid ".$links[$i]["icon"]. " icon\"></i>".$links[$i]["name"]."</p></a>\r\n";
		}
	}

    $twitchHtml = "\t<div class=\"side-bottom\">\r\n";

    if($user_info["logged_in"]){
        $twitchHtml .= "\t\t<a href=\"logout.php\"><p><i class=\"fa-brands fa-twitch icon\"></i>Log Out</p></a>\r\n";
	}else{
		$twitchHtml .= "\t\t<a href=\"".$twitch->get_oauth_url($state)."\"><p><i class=\"fa-brands fa-twitch icon\"></i>Login</p></a>\r\n";
	}

    $twitchHtml .= "\t</div>\r\n</div>";
    $html .= $twitchHtml;
    $html .= "<div class=\"main\">\r\n\t<div class=\"main-top\">\r\n";
    $html .= "\t\t<h3>$bot_name</h3>\r\n";
    $html .= page_logged_in();
    $html .= "\t</div>\r\n";
    $html .= "\t<div class=\"main-content\">\r\n";
    echo($html);
}

function page_title($cur_file){
	$cur_file = basename($cur_file);
	global $links;
	for($i = 0; $i < count($links); $i++){
		if($cur_file == $links[$i]["file"]){
			return $links[$i]["name"];
		}
	}
	return "Twitch Bot";
}

function redirect($url){
		$html = <<<EOD
<html>
<head>
<script>
window.location.replace("$url");
</script>
<body>
</body>
</html>
EOD;
	die($html);
}

//Redirect to index with an error message
function redirect_error($msg = ""){
	$msg = preg_replace("/[^A-Za-z0-9 ]/", '', $msg);
	redirect("index.php?msg=$msg");
}

function redirect_unauthorized(){
	if(!is_authorized()){
		$msg = "unauthorized";
		redirect_error($msg);
	}
}

function page_unauthorized(){
	
}

function bot_process_running(){
	$bot_cmd = "chillbot.py";
	$execstring="ps aux";
	$output="";
	exec($execstring, $output);
	
	foreach($output as $line){
		$pos = strpos($line, $bot_cmd);
		return is_numeric($pos);
	}
	return False;
}

function bot_start_process(){
	$bot_cmd = "chillbot.py";
	$execstring='bash -c "exec nohup setsid python3 ~/chillbot/bot/chillbot.py > /dev/null 2>&1 &"';
	exec($execstring);
}

function page_footer(){
    $footerHtml = "\t\t</div>\r\n</div>\r\n</body>\r\n</html>";
    echo($footerHtml);
}
?>