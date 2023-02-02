<?php
require_once("./inc/bot.php");

//If user is not an authorized user, redirect to index
redirect_unauthorized();

//Create DB Connection
$db = new Database($config["database"]);
if(!$db->connect()){
	//To-Do: show proper error msg
	redirect_error("dbconnecterror");
}

$timezone_select_html = "";

$raids = $db->get_raids();

$table_html = "<table id=\"song-table\" class=\"styled-table center\">\n";
$table_html .= "\t<tr>\n";
$table_html .= "\t\t<th>Raider</th>\n";
$table_html .= "\t\t<th>Viewers</th>\n";
$table_html .= "\t\t<th><div style='position: relative;'><div>Timestamp</div><div class='timezone' id='tz-div'>TimeZone: GMT</div></div></th>\n";
$table_html .= "\t</tr>\n";

foreach($raids as $row){
    $user = $row["user_name"];
    $viewers = $row["viewers"];
    $timestamp = $row["timestamp"];
    $table_html .= "\t<tr>\n";
    $table_html .= "\t\t<td>$user</td>\n";
    $table_html .= "\t\t<td>$viewers</td>\n";
    $table_html .= "\t\t<td name='raid-timestamp'>$timestamp</td>\n";
    $table_html .= "\t</tr>\n";
}

$table_html .= "</table>"
?>

<?php sidenav(__FILE__); ?>

<div class="center" style="width: 80%; position: relative;">
<h1 class="" style="margin: 0; padding-bottom: 10px; text-align: center;"> Raids </h1>
<?php echo($table_html); ?>
<div>
    <div style="float: left; margin-top: 10px;">
        <label for="days">Raids Within </label>
        <select name="days" id="days">
            <option value="1">24 Hours</option>
            <option value="7">7 Days</option>
            <option value="30">30 Days</option>
            <option value="365">Year</option>
            <option value="all">All Time</option>
        </select> 
    </div>
    <div style="float: right;">
        <button id="discord_button" class="styled-button" type="button" onclick="">Send to Discord</button>
    </div>
</div>

<script>
    function formatRaidTimes(){
        var tz = getTimeZone();
        var tz_element = document.getElementById("tz-div");
        tz_element.innerHTML = "Time Zone: "+escapeHtml(tz);
        var elements = document.getElementsByName("raid-timestamp");
        for(var i = 0; i < elements.length; i++){
            var ts = elements[i].innerHTML;
            var parts = ts.split(" ");
            ts = parts[0]+"T"+parts[1]+"Z";
            var date = new Date(ts);
            var formatted = formatDate(date, tz);
            elements[i].innerHTML = escapeHtml(formatted);
        }
    }
    //call onload
    formatRaidTimes();
</script>

<?php
	page_footer();
?>
