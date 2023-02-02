<?php
require_once("../inc/config.php");
require_once("../inc/db.php");

$WEBHOOK_LOG = "../log/webhook.log";
$EVENTSUB_LOG = "../log/eventsub.log";

$SECRET = "thisismysecret";

$headers = getallheaders();
$body = file_get_contents('php://input');

webhook_log($headers, $body);

//Verify the request signature
if(!verify_request($headers, $body)){
    http_response_code(400);
    die();
}

$msg_type = strtolower($headers["Twitch-Eventsub-Message-Type"]);
$body = json_decode($body);

switch($msg_type){
    //If Challenge Request, response with the hmac
    case "webhook_callback_verification":
        challenge_response($headers, $body);
        break;
    case "notification":
        //We got a notification!
        process_notification($headers, $body);
        break;
    case "revocation":
        //Subscription has been revoked
        process_revocation($headers, $body);
        break;
    default:
        //Unsupported Message Type
        http_response_code(400);
        die();
}

//Process a Twitch Notification
function process_notification($headers, $body){
    global $config;
    switch($body->subscription->type){
        case "channel.raid":
            //RAID!
            $raider_id = $body->event->from_broadcaster_user_id;
            $raider_name = $body->event->from_broadcaster_user_name;
            $viewers = $body->event->viewers;
            
            //Log Raid to DB
            //Create DB Connection
            $result = False;
            $db = new Database($config["database"]);
            if(!$db->connect()){
                eventsub_log("Error Connecting to DB to Insert Raid");
                $str = "Type: Raid - Raider: $raider_name($raider_id) - Viewers: $viewers - DB: $result";
                eventsub_log($str);
                die();
            }
            $result = $db->add_raid($raider_name, $raider_id, $viewers);
            $str = "Type: Raid - Raider: $raider_name($raider_id) - Viewers: $viewers - DB: $result";
            eventsub_log($str);
        break;
    }
    die();
}
    


function process_revocation($headers, $body){
    $id = $body->subscription->id;
    $status = $body->subscription->status;
    $type = $body->subscription->type;
    eventsub_log("Subscription Revoked ($id) - Type: $type - Reason: $status");
    switch($status){
        case "user_removed":
            //TOTO
            break;
        case "authorization_revoked":
            //TODO
            break;
        case "notification_failures_exceeded":
            //TODO
            break;
        case "version_removed":
            //TODO
            break;
        default:
            //Unknown Revocation Reason
            //TODO
    }
    die();
}

//Verify the webhook signature
function verify_request($headers, $body){
    global $SECRET;
    $hmac_data = $headers["Twitch-Eventsub-Message-Id"];
    $hmac_data .= $headers["Twitch-Eventsub-Message-Timestamp"];
    $hmac_data .= $body;
    $hmac = "sha256=".hash_hmac("sha256", $hmac_data, $SECRET);
    if($hmac == $headers["Twitch-Eventsub-Message-Signature"]){
        return TRUE;
    }
    return FALSE;
}

//Response to a verification challenge
function challenge_response($headers, $body){
    header("Content-Type: text/plain");
    die($body->challenge);
}

//Write to the eventsub log
function eventsub_log($msg){
    global $EVENTSUB_LOG;
    $msg .= "\n";
    $file = fopen($EVENTSUB_LOG, "a");
    fwrite($file, $msg);
    fclose($file);
}

//Log a webhook request
function webhook_log($headers, $body){
    global $WEBHOOK_LOG;
    $log_data = "=====================================================================\n\n";
    $header_names = array_keys($headers);
    
    foreach($header_names as $name){
        $log_data .= $name . ": " . $headers[$name] . "\n";
    }

    $log_data .= "\n";
    $log_data .= $body."\n";
    $file = fopen($WEBHOOK_LOG, "a");
    fwrite($file, $log_data);
    fclose($file);
}
?>

