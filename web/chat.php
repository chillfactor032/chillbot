<?php
require_once("./inc/bot.php");

//If user is not an authorized user, redirect to index
redirect_unauthorized();

?>

<?php sidenav(__FILE__); ?>

<div class="center" style="width: 80%; position: relative;">
	<h1 class="" style="margin: 0; padding-bottom: 10px; text-align: center;"> Chat </h1>
</div>

<div style="text-align: center; width=100%;"><h2>TODO</h2></div>
<?php
	page_footer();
?>
