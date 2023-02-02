<?php
require_once("./inc/bot.php");

//If user is not an authorized user, redirect to index
redirect_unauthorized();

?>

<?php sidenav(__FILE__); ?>


<?php
	page_footer();
?>
