<?php
require_once("./inc/bot.php");

//If Unauthorized, return 401
if(!is_authorized()){
    http_response_code(401);
    die("Unauthorized");
}

if(isset($_GET["start"])){
    $response = [
        "success" => False,
        "msg" => "",
        "elapsed" => 0
    ];
    $starttime = microtime(true);
    $pid = bot_process_running();
    if($pid != -1){
        $response["success"] = True;
        $response["msg"] = "Bot already running. PID: ".$pid;
        header("Content-Type: application/json;");
        $json = json_encode($response, JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
        echo($json);
        die();
    }
    bot_start_service();
    $elapsed = microtime(true)-$starttime;
    while($elapsed  < 7 and $pid < 0){
        sleep(0.5);
        $pid = bot_process_running();
        $elapsed = microtime(true)-$starttime;
    }
    $response["elapsed"] = $elapsed;
    if($pid != -1){
        $response["success"] = True;
        $response["msg"] = "Bot Started. PID: ".$pid;
        header("Content-Type: application/json;");
        $json = json_encode($response, JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
        echo($json);
        die();
    }else{
        $response["success"] = False;
        $response["msg"] = "Bot not started.";
        $json = json_encode($response, JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
        echo($json);
        die();
    }
}

if(isset($_GET["stop"])){
    $response = [
        "success" => False,
        "msg" => "",
        "elapsed" => 0
    ];
    $pid = bot_process_running();
    if($pid == -1){
        $response["success"] = True;
        $response["msg"] = "Bot already stopped.";
        header("Content-Type: application/json;");
        $json = json_encode($response, JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
        echo($json);
        die();
    }
    bot_stop_service();
    $starttime = microtime(true);
    $elapsed = microtime(true)-$starttime;
    while($elapsed  < 7 and $pid >= 0){
        sleep(0.5);
        $pid = bot_process_running();
        $elapsed = microtime(true)-$starttime;
    }
    $response["elapsed"] = $elapsed;
    if($pid == -1){
        $response["success"] = True;
        $response["msg"] = "Bot stopped.";
        header("Content-Type: application/json;");
        $json = json_encode($response, JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
        echo($json);
        die();
    }else{
        $response["success"] = False;
        $response["msg"] = "Bot cannot be stopped.";
        header("Content-Type: application/json;");
        $json = json_encode($response, JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
        echo($json);
        die();
    }
}

if(isset($_GET["restart"])){
    $response = [
        "success" => False,
        "msg" => "",
        "elapsed" => 0
    ];
    $starttime = microtime(true);
    $oldpid = bot_process_running();
    $newpid = $oldpid;
    if($oldpid < 0){
        bot_start_service();
    }else{
        bot_restart_service();
    }
    $newpid = bot_process_running();
    $elapsed = microtime(true)-$starttime;
    while($elapsed < 7 and $oldpid == $newpid){
        sleep(0.5);
        $newpid = bot_process_running();
        $elapsed = microtime(true)-$starttime;
    }
    if($oldpid == $newpid){
        $response["success"] = False;
        $response["msg"] = "New process cant be started.";
    }else{
        $response["success"] = True;
        $response["msg"] = "New process started. PID: ".$newpid;
    }
    $response["elapsed"] = $elapsed;
    header("Content-Type: application/json;");
    $json = json_encode($response, JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
    echo($json);
    die();
}

//Create DB Connection
$db = new Database($config["database"]);

if(!$db->connect()){
	http_response_code(500);
    die("DB Connect Error");
}

function botstatus(){
    global $db;
    $twitch_heartbeat = $db->get_heartbeat("chillbot");
    $twitch_age = -1;
    if(empty($twitch_heartbeat)){
        $twitch_age = -1;
    }else{
        $twitch_age = $twitch_heartbeat["age"];
    }

    $db_heartbeat = $db->get_heartbeat("db");
    $db_age = -1;
    if(empty($db_heartbeat)){
        $db_age = -1;
    }else{
        $db_age = $db_heartbeat["age"];
    }

    $pid = bot_process_running();
    $status_str = "";
    $status_arr = [
        "pid" => $pid,
        "db" => $db_age,
        "twitch" => $twitch_age
    ];
    return $status_arr;
}

function eventsubs(){
    global $twitch;
    global $config;
    $eventsubs = $twitch->get_eventsubs();
    $eventsubs_obj = [];

    foreach($config["eventsubs"]["json"] as $es){
        $arr = [
            "type" => $es["type"],
            "status" => "authorization_revoked",
            "id" => "",
            "data" => []
        ];
        array_push($eventsubs_obj, $arr);
    }

    for($i = 0; $i < count($eventsubs_obj); $i++){
        for($j = 0; $j < count($eventsubs["data"]); $j++){
            if($eventsubs_obj[$i]["type"] == $eventsubs["data"][$j]["type"]){
                $eventsubs_obj[$i]["id"] = $eventsubs["data"][$j]["id"];
                $eventsubs_obj[$i]["status"] = $eventsubs["data"][$j]["status"];
                $eventsubs_obj[$i]["data"] =  $eventsubs["data"][$j];
            }
        }
    }

    return $eventsubs_obj;
}

function topchatters(){
    global $db;
    $top_chatters = $db->top_chatters();
    if(count($top_chatters) > 0){
        return $top_chatters;
    }
    return [];
}

function raid(){
    global $db;
    $raids = $db->get_raids(1);
    if(count($raids) > 0){
        return $raids;
    }
    return [];
}

$card = "all";
$response_arr = [
    "botstatus" => [],
    "eventsubs" => [],
    "topchatters" => [],
    "raid" => []
];

if(isset($_GET["card"])){
    switch($_GET["card"]){
        case "botstatus":
            $response_arr["botstatus"] = botstatus();
            break;
        case "eventsubs":
            $response_arr["eventsubs"] = eventsubs();
            break;
        case "topchatters":
            $response_arr["topchatters"] = topchatters();
            break;
        case "raid":
            $response_arr["raid"] = raid();
            break;
        default:
            $response_arr = allcards();
    }
}else{
    $response_arr = allcards();
}

header("Content-Type: application/json;");
$json = json_encode($response_arr, JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
echo($json);
die();

function allcards(){
    $arr = [
        "botstatus" => botstatus(),
        "eventsubs" => eventsubs(),
        "topchatters" => topchatters(),
        "raid" => raid()
    ];
    return $arr;
}

?>