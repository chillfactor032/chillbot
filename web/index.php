<?php
require_once("./inc/bot.php");

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
?>

<?php sidenav(__FILE__); ?>

<h2>Sidebar</h2>

<p>This sidebar is of full height (100%) and always shown.</p>
<p>Scroll down the page to see the result.</p>
<p>Some text to enable scrolling.. Lorem ipsum dolor sit amet, illum definitiones no quo, maluisset concludaturque et eum, altera fabulas ut quo. Atqui causae gloriatur ius te, id agam omnis evertitur eum. Affert laboramus repudiandae nec et. Inciderint efficiantur his ad. Eum no molestiae voluptatibus.</p>
<p>Some text to enable scrolling.. Lorem ipsum dolor sit amet, illum definitiones no quo, maluisset concludaturque et eum, altera fabulas ut quo. Atqui causae gloriatur ius te, id agam omnis evertitur eum. Affert laboramus repudiandae nec et. Inciderint efficiantur his ad. Eum no molestiae voluptatibus.</p>
<p>Some text to enable scrolling.. Lorem ipsum dolor sit amet, illum definitiones no quo, maluisset concludaturque et eum, altera fabulas ut quo. Atqui causae gloriatur ius te, id agam omnis evertitur eum. Affert laboramus repudiandae nec et. Inciderint efficiantur his ad. Eum no molestiae voluptatibus.</p>
<p>Some text to enable scrolling.. Lorem ipsum dolor sit amet, illum definitiones no quo, maluisset concludaturque et eum, altera fabulas ut quo. Atqui causae gloriatur ius te, id agam omnis evertitur eum. Affert laboramus repudiandae nec et. Inciderint efficiantur his ad. Eum no molestiae voluptatibus.</p>

<?php page_footer(); ?>



