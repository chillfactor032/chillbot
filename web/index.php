<?php
require_once("./inc/bot.php");

$alerts_list = ["unauthorized","success"];

$msg = "";

if(isset($_GET["msg"]) AND in_array($_GET["msg"], $alerts_list)){
	$msg = $_GET["msg"];
}

if(isset($_GET["code"]) AND isset($_GET["state"])){
	//Check if state matches
	if($_GET["state"] == $_SESSION["twitch_state"]){
		//Exchange the auth code for an oauth token
		$result = $twitch->get_oauth_token($_GET["code"]);
		if($result){
			$_SESSION["twitch_auth"] = $result;
			$result = $twitch->get_user_info($result["access_token"]);
			if($result){
				$_SESSION["twitch_user"] = $result["data"][0];
				$msg = "success";
			}
		}
	}
}

$showToast = "";
switch($msg){
	case "unauthorized":
		$showToast  = <<<EOD
		<script>
			setTimeout(function() {
				unauthorizedMessage();
			}, 1500);
		</script>
EOD;
		break;
	case "success":
$showToast  = <<<EOD
		<script>
			setTimeout(function() {
				loggedInMessage();
			}, 1500);
		</script>
EOD;
		break;
}

function unauthorized_page(){
	sidenav(__FILE__);
	$html = <<<HTML
<div class="center" style="width: 80%; position: relative;">
<h1 class="" style="margin: 0; padding-bottom: 10px; text-align: center;"> Home </h1>
<p>
<h2 style="text-align: center;">Please login to continue</h2>
HTML;
	echo($html);
	page_footer();
	die();
}

//Show the unauthorized page if not logged in
if(!is_authorized()){
	unauthorized_page();
}

?>

<?php sidenav(__FILE__); ?>

<div class="center" style="width: 80%; position: relative;">
	<h1 class="" style="margin: 0; padding-bottom: 10px; text-align: center;"> Dashboard </h1>
</div>
<div class="cards" style="">
	<article class="card">
		<h2 class="heading"> Bot Status </h2>
		<div class="text">
			<table style="width: 90%; margin: auto; border-collapse: collapse;">
				<tr>
					<td class="item">Status:</td>
					<td class="alive"><i class="fa-solid fa-circle-check"></i></td>
				</tr>
				<tr>
					<td class="item">Database:</td>
					<td class="warning"><i class="fa-solid fa-triangle-exclamation"></i></td>
				</tr>
				<tr>
					<td class="item">Twitch Chat:</td>
					<td class="dead"><i class="fa-solid fa-skull"></i></td>
				</tr>
			</table>
			<div style="margin: 10px auto 10px auto; text-align: center;">
				<button>Stop</button>
				<button>Start</button>
				<button>Restart</button>
				<p>
					This feature is not live yet. Stay tuned.
				</p>
			</div>
		</div>
		<div id="botstatus-status-img" style="margin: 0; padding:0"><i class="fa-solid fa-circle-check status-gif"></i></div>
		<button style="position: absolute; bottom: 10px; left: 10px;"><i class="fa-solid fa-arrows-rotate"></i></button>
		<div id="botstatus-updatetime-label" style="position: absolute; bottom: 10px; right: 10px;">Waiting to update</div>
		<input id="botstatus-updatetime-ms" type="hidden" value="0" />
	</article>
	<article class="card">
		<h2 class="heading"> Event Subscriptions </h2>
		<div class="text">
			<table id="eventsubs-table" class="status-table">
				<tr>
					<th >Event</th>
					<th>Status</th>
				</tr>
			</table>
			<div style="margin: 10px auto 10px auto; text-align: center;">
			</div>
		</div>
		<div id="eventsubs-status-img" style="margin: 0; padding:0"><i class="fa-solid fa-circle-check status-gif"></i></div>
		<button style="position: absolute; bottom: 10px; left: 10px;"><i class="fa-solid fa-arrows-rotate"></i></button>
		<div id="eventsubs-updatetime-label" style="position: absolute; bottom: 10px; right: 10px;">Waiting to update</div>
		<input id="eventsubs-updatetime-ms" type="hidden" value="0" />
	</article>
	<article class="card">
		<h2 class="heading"> Top Chatters </h2>
		<div class="text">
			<table id="topchatters-table" class="status-table">
				<tr>
					<th>User</td>
					<th>Msgs</td>
				</tr>
			</table>
		</div>
		<div id="topchatters-status-img" style="margin: 0; padding:0"><i class="fa-solid fa-circle-check status-gif"></i></div>
		<button style="position: absolute; bottom: 10px; left: 10px;" onclick="updateChatters();"><i class="fa-solid fa-arrows-rotate"></i></button>
		<div id="topchatters-updatetime-label" style="position: absolute; bottom: 10px; right: 10px;">Waiting to update</div>
		<input id="topchatters-updatetime-ms" type="hidden" value="0" />
	</article>
	<article class="card">
		<h2 class="heading"> Raid Quickview </h2>
		<div class="text">
			<table id="raid-table" class="status-table">
				<tr>
					<th>Raider</td>
					<th>Viewers</td>
				</tr>
			</table>
		</div>
		<div id="raids-status-img" style="margin: 0; padding:0"><i class="fa-solid fa-circle-check status-gif"></i></div>
		<button style="position: absolute; bottom: 10px; left: 10px;" onclick="updateRaids();"><i class="fa-solid fa-arrows-rotate"></i></button>
		<div id="raids-updatetime-label" style="position: absolute; bottom: 10px; right: 10px;">Waiting to update</div>
		<input id="raids-updatetime-ms" type="hidden" value="0" />
	</article>
</div>
<script type="text/javascript" src="status.js"></script>
<script>


var update_arr = [{
		"label": "botstatus-updatetime-label",
		"value": "botstatus-updatetime-ms",
		"msg": "now"
	},{
		"label": "eventsubs-updatetime-label",
		"value": "eventsubs-updatetime-ms",
		"msg": "now"
	},{
		"label": "topchatters-updatetime-label",
		"value": "topchatters-updatetime-ms",
		"msg": "now"
	},{
		"label": "raids-updatetime-label",
		"value": "raids-updatetime-ms",
		"msg": "now"
	}
];

//Only update EventSubs on page load and manually
updateEventsubs();

var tick = 0;
setInterval(() => {
	//Fires Every Minute
	if(tick % 60 == 0){
		updateChatters()
		updateRaids();
		tick = 0;
	}
	updateUpdateTimes();
	tick++;
}, 1000);

function updateUpdateTimes(){
	var label_element = null;
	var ts_element = null;
	var now_ms = Date.now();
	var ms = 0;
	var update_str = "";

	for(let i = 0; i < update_arr.length; i++){
		label_element = document.getElementById(update_arr[i]["label"]);
		ts_element = document.getElementById(update_arr[i]["value"]);
		ms = parseInt(ts_element.value);
		if(ms == 0){
			label_element.innerHTML = "Waiting to update";
			continue;
		}
		update_str = msToTimeDelta(now_ms-ms);
		if(label_element.innerHTML != "Updated "+update_str){
			label_element.innerHTML = "Updated "+update_str;
		}
	}
}

</script>
<?php echo($showToast) ?>
<?php page_footer(); ?>



