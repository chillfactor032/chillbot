<?php
require_once("./inc/bot.php");

//If user is not an authorized user, redirect to index
redirect_unauthorized();

$candidates = array();
$vote_closed = false;
$vote_winner_msg = "";
$cur_vote = 0;
$page_mode = "view";
$buttons_html = "";
$monitor_script_html = "";
$page_header = "Live Learn Voting";

//Create DB Connection
$db = new Database($config["database"]);

if(!$db->connect()){
	//To-Do: show proper error msg
	redirect_error("dbconnecterror");
}

//Get the current open vote id, or 0 if none
$cur_vote = $db->current_vote();

//Get most recent vote that is closed
$prev_vote = $db->prev_vote();
$prev_vote_html = "";
if($prev_vote > 0){
	$prev_vote_html = <<<HTML
<div style="float: left;">
		<button id="view_last_poll_btn" class="styled-button" type="button" onclick="window.location.href = 'livelearns.php?vote=$prev_vote';">View Last Poll</button>
	</div>
HTML;
}

//First process the create vote form if present
if($cur_vote == 0 && isset($_POST["songs"])){
	$cur_vote = $db->create_vote();
	if($cur_vote == 0){
		redirect_error("votenotcreated");
	}
	$candidates = array();
	$ballot_num = 1;
	
	for($i = 0; $i < count($_POST["songs"]); $i++){
		//Skip rows with empty song name
		if(strlen(trim($_POST["songs"][$i]["name"])) > 0){
			$candidate = array(
				"ballot_num" => $ballot_num,
				"name" => $_POST["songs"][$i]["name"],
				"req" => $_POST["songs"][$i]["req"],
				"tm" => 0,
				"pr" => 0);
			if(isset($_POST["songs"][$i]["tm"]) && $_POST["songs"][$i]["tm"] == "tm"){
				$candidate["tm"] = 1;
			}
			//If pool room, set ballot num high and dont increment $ballot_num
			if(isset($_POST["songs"][$i]["pr"]) && $_POST["songs"][$i]["pr"] == "pr"){
				$candidate["pr"] = 1;
				$candidate["ballot_num"] = $POOL_ROOM_BALLOT;
			}else{
				$ballot_num++;
			}
			$candidates[] = $candidate;
		}
	}
	if(count($candidates) > 0){
		$db->insert_candidates($cur_vote, $candidates);
		$cur_vote = $db->current_vote();
		$candidates = $db->get_votes($cur_vote);
	}
	$cur_vote = $db->current_vote();
}

//If the close vote var is present, close the open vote
if($cur_vote > 0 && isset($_REQUEST["closevote"])){
	//The vote is over!
	$vote_closed = $db->close_vote($cur_vote);
	if($vote_closed){
		redirect("livelearns.php?vote=".$cur_vote);
	}else{
		redirect_error("votenotclosed");
	}
}

/*
* Determin which page mode: view/monitor/create/invalid
* View: show static vote results
* Monitor: Monitor live vote, update page every 5 secs with vote count
* Create: Show the Create Vote form
* Invalid: Invalid vote_id provided, show an error message
*/
if(isset($_GET["vote"])){
	$vote_id_get = intval($_GET["vote"]);
	$vote_status = $db->vote_status($vote_id_get);
	if(!$vote_status){
		$vote_status = "invalid";
	}
	
	if($cur_vote == $vote_id_get){
		//If the current open vote matches the provided vote id
		$page_mode = "monitor";
	}elseif($vote_status != "invalid"){
		//vote_id is valid, but closed, we show static results
		$page_mode = "view";
		$cur_vote = $vote_id_get;
	}else{
		$page_mode = "invalid";
	}
}else{
	if($cur_vote == 0){
		//No open vote and no provided vote_id to view, show create vote form
		$page_mode = "create";
	}else{
		//No provided vote_id but there is a current open vote, show monitor
		$page_mode = "monitor";
	}
}

/*
* Get PreReqs for each page mode
*/
if($page_mode == "view"){
	$page_header = "Live Learn Poll: Results";
	$candidates = $db->get_votes($cur_vote);
	$winners = array();
	$pool_room_winners = array();
	$max = 0;
	$total = 0;
	for($i = 0; $i < count($candidates); $i++){
		if($candidates[$i]["vote_cnt"] > $max){
			$max = $candidates[$i]["vote_cnt"];
		}
		if($candidates[$i]["pr"] == 0){
			$total += $candidates[$i]["vote_cnt"];
		}
	}
	
	if($total == 0){
		$total = 1;
	}

	for($i = 0; $i < count($candidates); $i++){
		if($candidates[$i]["vote_cnt"] == $max){
			$candidates[$i] += ["percent" => round(($candidates[$i]["vote_cnt"]/$total)*100.0, 0)];
			$winners[] = $candidates[$i];
		}
		if($candidates[$i]["pr"] > 0){
			$pool_room_winners[] = $candidates[$i];
		}
	}
	
	//Loop through the vote winners
	for($i = 0; $i < count($winners); $i++){
		switch($i){
			case 0:
				if(count($winners)>1){
					$vote_winner_msg .= "<p>The <b>first</b> ";
				}else{
					$vote_winner_msg .= "<p>The ";
				}
				break;
			case 1:
				$vote_winner_msg .= "<br>The <b>second</b> ";
				break;
			case 2:
				$vote_winner_msg .= "<br>The <b>third</b> ";
				break;
			default:
				$vote_winner_msg .= "<br>The <b>".$i."th</b> ";
		}
		$vote_winner_msg .= "winner of Live Learn of the Stream is ";
		$vote_winner_msg .= "<b>Live Learn ". htmlentities($winners[$i]["ballot_num"]) . " " .htmlentities($winners[$i]["name"]) . "</b> requested by <b>" . htmlentities($winners[$i]["requester"]) ."</b>";
		$vote_winner_msg .= " with " . $winners[$i]["vote_cnt"] . " votes ";
		$vote_winner_msg .= "(".$winners[$i]["percent"]."%)";
	}
	
	//Loop through the pool room winners
	for($i = 0; $i < count($pool_room_winners); $i++){
		switch($i){
			case 0:
				if(count($pool_room_winners)>1){
					$vote_winner_msg .= "<p>The <b>first</b> ";
				}else{
					$vote_winner_msg .= "<p>The ";
				}
				break;
			case 1:
				$vote_winner_msg .= "<br>The <b>second</b> ";
				break;
			case 2:
				$vote_winner_msg .= "<br>The <b>third</b> ";
				break;
			default:
				$vote_winner_msg .= "<br>The <b>".$i."th</b> ";
		}
		$vote_winner_msg .= "Live Learn going straight to the Pool Room is ";
		$vote_winner_msg .= "<b>".$pool_room_winners[$i]["name"] . "</b> requested by <b>" . $pool_room_winners[$i]["requester"] ."</b>";
	}
	
}elseif($page_mode == "monitor"){
	$page_header = "Live Learn Poll: In Progress";
	$candidates = $db->get_votes($cur_vote);
}elseif($page_mode == "create"){
	$page_header = "Create Live Learn Poll";
}elseif($page_mode == "invalid"){
	//Invalid Vote ID
}else{
	//Unknown Page Mode!
}
?>

<?php sidenav(__FILE__); ?>

<?php
$html = "";

/*
* Generate Page Content based on page mode
*/
if($page_mode == "view"){
	//Expected values: candidates, cur_vote, monitor script, buttons_html
	$html = <<<EOD
<div class="center" style="width: 80%;" >
<h1 class="" style="margin: 0; padding-bottom: 10px; text-align: center;"> $page_header </h1>
<div class="winner-text">
$vote_winner_msg
</div>
<form method="GET" action="livelearns.php">
<input type="hidden" id="vote_id" name="vote" value="$cur_vote">
<input type="hidden" name="closevote" value="true">
<table id="song-table" class="styled-table center" style="">
	<tr>
		<th style="width: 50px; text-align: right;">&nbsp;</th>
		<th style="width: 50px;">TM</th>
		<th style="width: auto;">Song - Artist</th>
		<th style="width: 20%;">Requested By</th>
		<th style="width: 50px;">PR</th>
		<th style="width: 50px;">Votes</th>
	</tr>
EOD;
	$total = 0;
	for($i = 0; $i < count($candidates); $i++){
		$winner_style = "";

		//Add bold style to the winner row
		for($x = 0; $x < count($winners); $x++){
			if($winners[$x]["ballot_num"] == $candidates[$i]["ballot_num"]){
				$winner_style = "winner-row";
			}
		}
		
		$ballot_num = htmlentities($candidates[$i]["ballot_num"]);
		$ballot_num_html = $ballot_num . ":";
		$name = htmlentities($candidates[$i]["name"]);
		$req = htmlentities($candidates[$i]["requester"]);
		$pr = "";
		$vote_cnt = $candidates[$i]["vote_cnt"];
		if($candidates[$i]["pr"] > 0){
			//$pr = "checked";
			$pr = "<img src=\"img/ico_check.png\" style=\"width:20px; height: auto;\">";
			$vote_cnt = "PR";
			$ballot_num_html = "&nbsp;";
		}else{
			$pr = "<img src=\"img/ico_blank.png\" style=\"width:20px; height: auto;\">";
			$total+=$vote_cnt;
		}
		$tm = "";
		if($candidates[$i]["tm"] > 0){
			//$tm = "checked";
			$tm = "<img src=\"img/ico_check.png\" style=\"width:20px; height: auto;\">";
		}else{
			$tm = "<img src=\"img/ico_blank.png\" style=\"width:20px; height: auto;\">";
		}
		
		$fragment = <<<EOD
	<tr class="$winner_style" style="" id="row-$ballot_num">
		<td style="text-align: right;">
			$ballot_num_html
		</td>
		<td style="text-align: center;">
			$tm
		</td>
		<td id="song$ballot_num-name">$name</td>
		<td id="song$ballot_num-req">$req</td>
		<td style="text-align: center;">
			$pr
		</td>
		<td id="vote-$ballot_num">
			$vote_cnt
		</td>
	</tr>
EOD;
		$html .= $fragment;
	}

$end = <<<EOD
	<tr>
		<th>&nbsp;</th>
		<th>&nbsp;</th>
		<th>&nbsp;</th>
		<th colspan="2" style="text-align: right;">Total:</th>
		<th id="vote-total">$total</th>
	</tr>
</table>
<div style="float: right;">
    <button id="discord_button" class="styled-button" type="button" onclick="sendDiscord();">Send to Discord</button>
	<button class="styled-button" type="button" onclick="window.location.href = 'livelearns.php';">New Poll</button>
</div>
</form>
</div>
EOD;
		$html .= $end;
}elseif($page_mode == "monitor"){
	//Expected values: candidates, cur_vote, monitor script, buttons_html
	$html = <<<EOD
<div class="center" style="width: 80%;" >
<h1 class="" style="margin: 0; padding-bottom: 10px; text-align: center;"> $page_header </h1>
<form method="GET" action="livelearns.php">
<input type="hidden" name="vote" value="$cur_vote">
<input type="hidden" name="closevote" value="true">
<table id="song-table" class="styled-table center">
	<tr>
		<th style="width: 50px; text-align: right;">&nbsp;</th>
		<th style="width: 50px;">TM</th>
		<th style="width: auto;">Song - Artist</th>
		<th style="width: 20%;">Requested By</th>
		<th style="width: 50px;">PR</th>
		<th style="width: 50px;">Votes</th>
	</tr>
EOD;
	$total = 0;
	for($i = 0; $i < count($candidates); $i++){
		$ballot_num = $candidates[$i]["ballot_num"];
		$ballot_num_html = $ballot_num .":";
		$name = $candidates[$i]["name"];
		$req = $candidates[$i]["requester"];
		$pr = "";
		$vote_cnt = $candidates[$i]["vote_cnt"];
		if($candidates[$i]["pr"] > 0){
			//$pr = "checked";
			$pr = "<img src=\"img/ico_check.png\" style=\"width:20px; height: auto;\">";
			$vote_cnt = "PR";
			$ballot_num_html = "&nbsp;";
		}else{
			$pr = "<img src=\"img/ico_blank.png\" style=\"width:20px; height: auto;\">";
			$total+=$vote_cnt;
		}
		$tm = "";
		if($candidates[$i]["tm"] > 0){
			//$tm = "checked";
			$tm = "<img src=\"img/ico_check.png\" style=\"width:20px; height: auto;\">";
		}else{
			$tm = "<img src=\"img/ico_blank.png\" style=\"width:20px; height: auto;\">";
		}
		
		$fragment = <<<EOD
	<tr id="row-$ballot_num">
		<td style="text-align: right;">
			$ballot_num_html
		</td>
		<td style="text-align: center;">
			$tm
		</td>
		<td id="song$ballot_num-name">$name</td>
		<td id="song$ballot_num-req">$req</td>
		<td style="text-align: center;">
			$pr
		</td>
		<td id="vote-$ballot_num">
			$vote_cnt
		</td>
	</tr>
EOD;
		$html .= $fragment;
	}

$end = <<<EOD
	<tr>
		<th>&nbsp;</th>
		<th>&nbsp;</th>
		<th>&nbsp;</th>
		<th colspan="2" style="text-align: right;">Total:</th>
		<th id="vote-total">$total</th>
	</tr>
</table>
<div style="float: right;">
	<input class="styled-button" type="submit" value="Close Poll" >
</div>
</form>
<script>
	setInterval(getVoteUpdate, 5000);
</script>
</div>
EOD;
	$html .= $end;
}elseif($page_mode == "create"){
	$html = <<<EOD
<div class="center" style="width: 80%;" >
<h1 class="" style="margin: 0; padding-bottom: 10px; text-align: center;"> $page_header </h1>
<form method="POST" onsubmit="localStorage.removeItem('state')" action="livelearns.php">
<table id="song-table" class="styled-table center" style="">
	<tr>
		<th style="width: 50px; text-align: right;">&nbsp;</th>
		<th style="width: 50px;">TM</th>
		<th style="width: auto;">Song - Artist</th>
		<th style="width: 20%;">Requested By</th>
		<th style="width: 50px;">PR</th>
		<th style="width: 50px;">Edit</th>
	</tr>
EOD;

	$cnt = 5;

	for($i = 0; $i < $cnt; $i++){
		$num = $i+1;
		$fragment = <<<EOD
	<tr>
		<td style="text-align: right;">
			$num:
		</td>
		<td style="text-align: center;">
			<input type="checkbox" id="song$i-tm" name="songs[$i][tm]" value="tm">
		</td>
		<td><input type="text" id="song$i-name" name="songs[$i][name]" value="" autocomplete="off"></td>
		<td><input type="text" id="song$i-req" name="songs[$i][req]" value="" autocomplete="off"></td>
		<td style="text-align: center;"><input type="checkbox" id="song$i-pr" name="songs[$i][pr]" value="pr"></td>
		<td>
			<a href="#" onclick="songTableAddRow('song-table', $i)"><img src="img/ico_add.png" style="width:20px; height: auto;" title="Add Row Below"></a>
			<a href="#" onclick="songTableRemoveRow('song-table', $i)"><img src="img/ico_del.png" style="width:20px; height: auto;" title="Remove This Row"></a>
		</td>
	</tr>
EOD;
		$html .= $fragment;
	}

$end = <<<EOD
</table>
<div>
	$prev_vote_html
	<div style="float: right;">
		<input class="styled-button" type="submit" value="Create Poll" >
	</div>
</form>
<script>
	loadCandidatesLS();
	setInterval(saveCandidatesLS, 10000);
</script>
</div>
EOD;
		$html .= $end;
}elseif($page_mode == "invalid"){
	$html = "Invalid Vote ID Provided!";
}else{
	//Unknown Page Mode!
	$html = "Unknown Page State";
}

echo($html);
?>

<?php
	page_footer();
?>

