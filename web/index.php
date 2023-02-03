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
	<h1 class="" style="margin: 0; padding-bottom: 10px; text-align: center;"> Home </h1>
</div>
<div class="cards" style="margin-left: 50px; margin-right: 50px;">
<article class="card">
	<img src="/img/squirrel.jpg" alt="Sample photo">
	<div class="text">
		<h3>Seamlessly visualize quality</h3>
		<p>Collaboratively administrate empowered markets via plug-and-play networks.</p>
		<button>Here's why</button>
	</div>
	</article>
	<article class="card">
	<img src="/img/squirrel.jpg" alt="Sample photo">
	<div class="text">
		<h3>Seamlessly visualize quality</h3>
		<p>Collaboratively administrate empowered markets via plug-and-play networks.</p>
		<button>Here's why</button>
	</div>
	</article>
	<article class="card">
	<img src="/img/squirrel.jpg" alt="Sample photo">
	<div class="text">
		<h3>Seamlessly visualize quality</h3>
		<p>Collaboratively administrate empowered markets via plug-and-play networks.</p>
		<button>Here's why</button>
	</div>
	</article>
</div>

<?php echo($showToast) ?>
<?php page_footer(); ?>



