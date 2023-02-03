<?php
require_once("./inc/bot.php");

//If user is not an authorized user, redirect to index
redirect_unauthorized();

$timestamp = date('Y-m-d H:i:s');

$number_emoji = [
    ":zero:",
    ":one:",
    ":two:",
    ":three:",
    ":four:",
    ":five:",
    ":six:",
    ":seven:",
    ":eight:",
    ":nine:"
];

function getWebhookJson_vote($vote_data, $vote_id){
    global $timestamp;
    global $number_emoji;
    $webhook_obj = [
        "embeds"=>[[
            "color" => 11601173,
            "title" => "Live Learn Poll Results",
            "url" => "https://twitchbot.chillaspect.com/livelearns.php?vote=".$vote_id,
            "fields" => [],
            "timestamp" => $timestamp,
            "footer" => [
                "text" => "Autoposted by MrFusion_Bot"
            ]]
        ]
    ];
    $spacer = [
        "name" => "",
        "value" => ""
    ];
    
    for($i = 0; $i < count($vote_data); $i++){
        $emoji_str = "";
        $chars = strval($i);
        $split = str_split($chars);
        foreach($split as $c){
            $num = intval($c);
            $emoji_str .= $number_emoji[$num];
        }
        //If not the first item, add the spacer
        if($i > 0){
            array_push($webhook_obj["embeds"][0]["fields"], $spacer);
        }
        $col1 = [
            "name" => "".$emoji_str." ".$vote_data[$i]["name"],
            "value" => "requested by ".$vote_data[$i]["requester"],
            "inline" => true
        ];
        $col2 = [
            "name" => $vote_data[$i]["vote_cnt"]." Votes",
            "value" => "",
            "inline" => true
        ];
        array_push($webhook_obj["embeds"][0]["fields"], $col1);
        array_push($webhook_obj["embeds"][0]["fields"], $col2);
    }
    return json_encode($webhook_obj, JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
}

function getWebhookJson_raid($raids, $days){
    global $timestamp;
    global $number_emoji;
    $webhook_obj = [
        "embeds"=>[
            [
                "color" => 11601173,
                "title" => "Live Learn Poll Results",
                "url" => "https://twitchbot.chillaspect.com/raids.php?days=".$days,
                "fields" => [],
                "timestamp" => $timestamp,
                "footer" => [
                    "text" => "Autoposted by MrFusion_Bot"
                ]
            ]
        ]
    ];
    $spacer = [
        "name" => "",
        "value" => ""
    ];
    for($i = 0; $i < count($raids); $i++){
        $emoji_str = "";
        $chars = strval($i);
        $split = str_split($chars);
        foreach($split as $c){
            $num = intval($c);
            $emoji_str .= $number_emoji[$num];
        }
        //If not the first item, add the spacer
        if($i > 0){
            array_push($webhook_obj["embeds"][0]["fields"], $spacer);
        }
        $col1 = [
            "name" => $emoji_str." ".$raids[$i]["user_name"],
            "value" => "",
            "inline" => true
        ];
        $col2 = [
            "name" => ":man_standing: ".$raids[$i]["viewers"],
            "value" => "",
            "inline" => true
        ];
        array_push($webhook_obj["embeds"][0]["fields"], $col1);
        array_push($webhook_obj["embeds"][0]["fields"], $col2);
    }
    return json_encode($webhook_obj, JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
}

function postJson($url, $json){
    $crl = curl_init($url);
    curl_setopt($crl, CURLOPT_POST, 1);
    curl_setopt($crl, CURLOPT_POSTFIELDS, $json);
    curl_setopt($crl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($crl);
    $http_status = curl_getinfo($crl, CURLINFO_HTTP_CODE);
    return $http_status;
}


//Create DB Connection
$db = new Database($config["database"]);

if(!$db->connect()){
    //To-Do: show proper error msg
    redirect_error("dbconnecterror");
}

if(isset($_GET["vote"])){
    $vote_id_get = intval($_GET["vote"]);
    $vote_status = $db->vote_status($vote_id_get);
    if(!$vote_status){
        $vote_status = "invalid";
        return;
    }
    if($vote_status == "closed"){
        $vote_data = $db->get_votes($vote_id_get, True);
        $json = getWebhookJson_vote($vote_data, $vote_id_get);
        $status = postJson($config["discord"]["vote_result_webhook_url"], $json);
        echo($status);
        die();
    }
}

if(isset($_GET["raid"])){
    $days = 1;
    if(isset($_GET["days"])){
        $days = intval($_GET["days"]);
    }
    $raid_count = 0;
    $raids = $db->get_raids($days);
    if($raids != 0){
        //Post Raids to Discord
        $json = getWebhookJson_raid($raids, $days);
        $status = postJson($config["discord"]["raid_webhook_url"], $json);
        echo($status);
        die();
    }
}

echo(500);

?>