<?php
require_once("inc/bot.php");

/*
$twitch = new Twitch($config["twitch"]["client_id"],$config["twitch"]["client_secret"],$config["twitch"]["redirect_url"]);

$secret = $config["eventsubs"]["secret"];
$user_id = $config["eventsubs"]["broadcaster_user_id"];
$stream_start_json = $config["eventsubs"]["json"][1];
$stream_start_json["transport"]["secret"] = $secret;
$stream_start_json["condition"]["broadcaster_user_id"] = $user_id;
print_r($twitch->delete_eventsub("b2991163-fc19-4931-b73f-61ed0e41677e"));
print_r($twitch->get_eventsubs());
*/
$out = bot_process_running();
echo("Bot Running: $out \n\n");
echo("Starting Bot... \n\n");
sleep(3);
$out = bot_process_running();
echo("Bot Running: $out \n\n");
?>