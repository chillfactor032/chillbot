<?php
require_once("./inc/bot.php");

//If user is not an authorized user, redirect to index
redirect_unauthorized();

$number_emoji = [
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

function getWebhookJson($vote_data, $vote_id){
    global $number_emoji;
    $webhook_obj = [
        "embeds"=>[[
            "color" => 11601173,
            "title" => "Live Learn Poll Results",
            "url" => "https://chillaspect.com/twitch/bot/livelearns.php?vote=".$vote_id,
            "fields" => [],
            "timestamp" => "2015-12-31T12:00:00.000Z",
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
        //If not the first item, add the spacer
        if($i > 0){
            array_push($webhook_obj["embeds"][0]["fields"], $spacer);
        }
        $col1 = [
            "name" => "".$number_emoji[$i]." ".$vote_data[$i]["name"],
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
$db = new Database($config["db"]["db_host"],
    $config["db"]["db_user"],
    $config["db"]["db_pass"],
    $config["db"]["db_name"]);

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
        $json = getWebhookJson($vote_data, $vote_id_get);
        $status = postJson($config["discord"]["vote_result_webhook_url"], $json);
        echo($status);
        die();
    }
}

echo(500);

?>