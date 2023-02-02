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

?>

<?php sidenav(__FILE__); ?>

<h2>Sidebar</h2>

<p>This sidebar is of full height (100%) and always shown.</p>
<p>Scroll down the page to see the result.</p>
<p>Some text to enable scrolling.. Lorem ipsum dolor sit amet, illum definitiones no quo, maluisset concludaturque et eum, altera fabulas ut quo. Atqui causae gloriatur ius te, id agam omnis evertitur eum. Affert laboramus repudiandae nec et. Inciderint efficiantur his ad. Eum no molestiae voluptatibus.</p>
<p>Some text to enable scrolling.. Lorem ipsum dolor sit amet, illum definitiones no quo, maluisset concludaturque et eum, altera fabulas ut quo. Atqui causae gloriatur ius te, id agam omnis evertitur eum. Affert laboramus repudiandae nec et. Inciderint efficiantur his ad. Eum no molestiae voluptatibus.</p>
<p>Some text to enable scrolling.. Lorem ipsum dolor sit amet, illum definitiones no quo, maluisset concludaturque et eum, altera fabulas ut quo. Atqui causae gloriatur ius te, id agam omnis evertitur eum. Affert laboramus repudiandae nec et. Inciderint efficiantur his ad. Eum no molestiae voluptatibus.</p>
<p>Some text to enable scrolling.. Lorem ipsum dolor sit amet, illum definitiones no quo, maluisset concludaturque et eum, altera fabulas ut quo. Atqui causae gloriatur ius te, id agam omnis evertitur eum. Affert laboramus repudiandae nec et. Inciderint efficiantur his ad. Eum no molestiae voluptatibus.</p>
<?php echo($showToast) ?>
<?php page_footer(); ?>



