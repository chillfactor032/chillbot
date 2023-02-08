<?php
require_once("./inc/bot.php");

//If Unauthorized, return 401
if(!is_authorized()){
    http_response_code(401);
    die("Unauthorized");
}

//Create DB Connection
$db = new Database($config["database"]);

if(!$db->connect()){
	http_response_code(500);
    die("DB Connect Error");
}

function botstatus(){
    return [];
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