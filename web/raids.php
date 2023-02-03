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

$days = 1;
if(isset($_GET["days"])){
    $days = intval($_GET["days"]);
}

$timezone_select_html = "";
$raid_count = 0;
$raids = $db->get_raids($days);

$table_html = "<table id=\"song-table\" class=\"styled-table center\">\n";
$table_html .= "\t<tr>\n";
$table_html .= "\t\t<th>Raider</th>\n";
$table_html .= "\t\t<th>Viewers</th>\n";
$table_html .= "\t\t<th><div style='position: relative;'><div>Timestamp</div><div class='timezone' id='tz-div'>TimeZone: GMT</div></div></th>\n";
$table_html .= "\t</tr>\n";

if($raids != 0){
    $raid_count = count($raids);
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
}
$table_html .= "</table>";

$options = [
    [
        "days" => 1,
        "text" => "24 Hours"
    ],[
        "days" => 7,
        "text" => "7 Days"
    ],[
        "days" => 30,
        "text" => "30 Days"
    ],[
        "days" => 365,
        "text" => "Year"
    ],[
        "days" => 99999,
        "text" => "All"
    ]
];
$options_html = "";
for($i = 0; $i < count($options); $i++){
    $cur_day = $options[$i]["days"];
    $cur_text = $options[$i]["text"];
    if($cur_day == $days){
        $options_html .= "\t\t\t\t<option value=\"$cur_day\" selected>$cur_text</option>\n";
    }else{
        $options_html .= "\t\t\t\t<option value=\"$cur_day\">$cur_text</option>\n";
    }
}
?>

<?php sidenav(__FILE__); ?>

<div class="center" style="width: 80%; position: relative;">
    <div style="position: relative;">
        <div style="Left: 0px; Bottom: 10px; position: absolute;">
            <label for="days" style="font-size: 1.5em;">Raids Within</label>
            <select onchange="daysChanged(this.value);" name="days" id="days" style="font-size: 1.5em;">
<?php echo($options_html); ?>
            </select> 
        </div>
        <h1 class="" style="margin: 0; padding-bottom: 10px; text-align: center;"> Raids </h1>
        <div style="Right: 0px; Bottom: 10px; position: absolute; font-size: 1.5em;">Total: <?php echo($raid_count); ?></div>
    </div>
<?php echo($table_html); ?>
<div>
    <div style="float: right;">
        <button id="discord_button" class="styled-button" type="button" onclick="window.location.href = 'discord.php?raid=1&days=1'">Send to Discord</button>
    </div>
</div>

<script>
    //Day Filter changed for raids
    function daysChanged(days){
        window.location.href = 'raids.php?days='+days;
    }

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
