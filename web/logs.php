<?php
require_once("./inc/bot.php");

//If user is not an authorized user, redirect to index
redirect_unauthorized();

$log_list = get_log_names();
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");
log_msg("This is a test", "app");

//If a log get param is set, echo log contents and exit
$since_line = 0;
if(isset($_GET["since"])){
    $since_line = intval($_GET["since"]);
}

if(isset($_GET["log"])){
    foreach($log_list as $log){
        if($log["name"] == $_GET["log"]){
            $log_dat = read_log($since_line, $log["name"]);
            header("Content-Type: application/json");
            echo($log_dat);
        }
    }
    die();
}

$log_select_html = "<select name=\"logs\" id=\"logs-select\" style=\"width: 100%;\" size=\"5\" multiple>\n";

foreach($log_list as $log){
    $log_select_html .= "\t<option value=\"". $log["name"] ."\">". $log["pretty_name"] ."</option>\n";
}
$log_select_html .= "</select>\n";

?>

<?php sidenav(__FILE__); ?>

<div class="center" style="width: 90%; position: relative;">
    <div style="position: absolute; left: 0; bottom: 10px;">
        Select a log to view:
    </div>
	<h1 class="" style="margin: 0; padding-bottom: 10px; text-align: center;" id="log-name"> Logs </h1>
</div>
<div class="center" style="width: 90%; position: relative;">
    <div id="log-container" style="">
        <div id="log-select-container" style="margin: 0 20px 0 0; width: 20%; float: left;">
            <?php echo($log_select_html); ?>
            <p>
            <input id="autoupdate-checkbox" type="checkbox" value="autoupdate" checked>&nbsp; Auto Update 10 secs</input>
            <p>
            <input id="tail-checkbox" type="checkbox" value="tail" checked>&nbsp; Scroll To Bottom</input>

            <p>Times are in GMT</p>
        </div>
        <div id="log-text-container" style="float: left; width: 70%">
            <textarea id="log-textarea" name="log-textarea" onscroll="textareaScroll();" readonly>
            </textarea>
        </div>
    </div>
</div>
<script>
var last_line = 0;

function textareaScroll(){
    let logtext = document.getElementById("log-textarea");
    const tail = document.getElementById("tail-checkbox");
    const bottom = logtext.scrollHeight - logtext.offsetHeight+4;

    //If scroll is at the bottom, recheck tail checkbox
    if(logtext.scrollTop == bottom){
        tail.checked = true;
    }else{
        tail.checked = false;
    }
}

async function autoupdateLog(){
    const check = document.getElementById("autoupdate-checkbox");
    const tail = document.getElementById("tail-checkbox");
    let logtext = document.getElementById("log-textarea");
    const logselect = document.getElementById("logs-select");
    
    if(check.checked && logselect.selectedIndex >= 0){
        const logname = logselect.value;
        //Fetch Log Data
        let response = await fetch("logs.php?log="+logname+"&since="+last_line);
        let json = await response.json();
        console.log(json);
        last_line = json["last_line"];
        if(last_line == 0){
            logtext.innerHTML = json["data"];
        }else{
            logtext.innerHTML = logtext.innerHTML + json["data"];
        }
        if(tail.checked){
            logtext.scrollTop = logtext.scrollHeight;
        }
    }
}

async function updateLog(event){
    const logname = event.target.value;
    last_line = 0;
    var logtext = document.getElementById("log-textarea");
    var logtitle = document.getElementById("log-name");

    logtitle.innerHTML = event.target.options[event.target.selectedIndex].text;
    
    //Fetch Log Data
    let response = await fetch("logs.php?log="+logname);
    let json = await response.json();
    if(json["data"].length == 0){
        text = "(log is empty)";
    }
    last_line = json["last_line"];
    logtext.innerHTML = json["data"];
    logtext.scrollTop = logtext.scrollHeight;
}

const logselect = document.getElementById("logs-select");
logselect.addEventListener('change', e => updateLog(e));

//Every 10 secs see if we need to fetch more log file
setInterval(autoupdateLog, 10000);

</script>
<?php page_footer(); ?>