const badge_json = {
    "VIP": "<img src=\"img/badges_vip.png\">",
    "SUB": "<i class=\"fa-solid fa-1\"></i>",
    "SUB2": "<i class=\"fa-solid fa-2\"></i>",
    "SUB3": "<i class=\"fa-solid fa-3\"></i>",
    "MOD": "<img src=\"img/badges_mod.png\">",
    "BROADCASTER": "<i class=\"fa-solid fa-video\"></i>",
};

const eventsubs_status = {
    "enabled": "<i class=\"alive fa-solid fa-circle-check\"></i>",
    "webhook_callback_verification_pending": "<i class=\"warning fa-solid fa-triangle-exclamation\"></i>",
    "webhook_callback_verification_failed": "<i class=\"dead fa-solid fa-skull\"></i>",
    "notification_failures_exceeded": "<i class=\"dead fa-solid fa-skull\"></i>",
    "authorization_revoked": "<i class=\"dead fa-solid fa-skull\"></i>",
    "moderator_removed": "<i class=\"dead fa-solid fa-skull\"></i>",
    "user_removed": "<i class=\"dead fa-solid fa-skull\"></i>"
};

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function controlBot(action){
    let stop_btn = document.getElementById("bot_stop_btn");
    let start_btn = document.getElementById("bot_start_btn");
    let restart_btn = document.getElementById("bot_restart_btn");
    let text_ele = document.getElementById("botstatus-text");

    text_ele.innerHTML = "<img src=\"img/status.gif\" style=\"height:24px; width:auto;\"> Working...";

    stop_btn.disabled = true;
    start_btn.disabled = true;
    restart_btn.disabled = true;

    let response = await fetch("status.php?"+ action +"=1");
    let json = await response.json();
    
    text_ele.innerHTML = json["msg"];

    stop_btn.disabled = false;
    start_btn.disabled = false;
    restart_btn.disabled = false;
}

async function updateBotStatus(){
    console.log("updateBotStatus");
    var e = document.getElementById("botstatus-status-symbol");
    e.innerHTML = "<img src=\"img/status.gif\" class=\"status-gif\">";
    let proc_status_element = document.getElementById("bot-process-status-cell");
    let db_status_element = document.getElementById("bot-db-status-cell");
    let twitch_status_element = document.getElementById("bot-twitchchat-status-cell");

    //Fetch Bot Status
    //Fetch Event Subs
    let response = await fetch("status.php?card=botstatus");
    let json = await response.json();

    let pid = json["botstatus"]["pid"];
    let db_age = json["botstatus"]["db"];
    let tc_age = json["botstatus"]["twitch"];

    if(pid < 0){
        proc_status_element.innerHTML = "&nbsp;<i class=\"fa-solid fa-skull\"></i> Not Running";
        proc_status_element.className = "dead";
    }else{
        proc_status_element.innerHTML = "&nbsp;<i class=\"fa-solid fa-circle-check\"></i> PID "+pid;
        proc_status_element.className = "alive";
    }

    if(db_age < 0){
        //Never Updated
        db_status_element.innerHTML = "&nbsp;<i class=\"fa-solid fa-skull\"></i> Never Updated";
        db_status_element.className = "dead";
    }else if(db_age <= 30){
        //Updated less than 30 secs... good to go
        db_status_element.innerHTML = "&nbsp;<i class=\"fa-solid fa-circle-check\"></i>";
        db_status_element.className = "alive";
    }else if(db_age < 60){
        //Updated over 30 secs ago but less than 1 min...warn
        db_status_element.innerHTML = "&nbsp;<i class=\"fa-solid fa-triangle-exclamation\"></i> "+db_age+"s";
        db_status_element.className = "warning";
    }else{
        //Update over 1 mins ago... down?
        db_status_element.innerHTML = "&nbsp;<i class=\"fa-solid fa-skull\"></i> Not Connected";
        db_status_element.className = "dead";
    }

    if(tc_age < 0){
        //Never Updated
        twitch_status_element.innerHTML = "&nbsp;<i class=\"fa-solid fa-skull\"></i> Never Updated";
        twitch_status_element.className = "dead";
    }else if(tc_age <= 30){
        //Updated less than 30 secs... good to go
        twitch_status_element.innerHTML = "&nbsp;<i class=\"fa-solid fa-circle-check\"></i>";
        twitch_status_element.className = "alive";
    }else if(tc_age < 60){
        //Updated over 30 secs ago but less than 1 min...warn
        twitch_status_element.innerHTML = "&nbsp;<i class=\"fa-solid fa-triangle-exclamation\"></i> "+db_age+"s";
        twitch_status_element.className = "warning";
    }else{
        //Update over 1 mins ago... down?
        twitch_status_element.innerHTML = "&nbsp;<i class=\"fa-solid fa-skull\"></i> Not Connected";
        twitch_status_element.className = "dead";
    }

    updateDiv = document.getElementById("botstatus-updatetime-ms");
    updateDiv.value = Date.now();
    e.innerHTML = "<i class=\"fa-solid fa-circle-check status-gif\"></i>";
}

async function updateEventsubs(){
    console.log("updateEventsubs");
    var e = document.getElementById("eventsubs-status-img");
    e.innerHTML = "<img src=\"img/status.gif\" class=\"status-gif\">";

    //Fetch Event Subs
    let response = await fetch("status.php?card=eventsubs");
    let json = await response.json();
    
    let table = document.getElementById("eventsubs-table");
    table.innerHTML = "";

    let tr = table.insertRow();
    let td1 = tr.insertCell();
    td1.appendChild(document.createTextNode("Event"));
    let td2 = tr.insertCell();
    td2.appendChild(document.createTextNode("Status"));
    let es_type = "";
    let es_status= "";

    for(let i = 0; i < json["eventsubs"].length; i++){
        es_type = json["eventsubs"][i]["type"];
        es_status = json["eventsubs"][i]["status"];
        symbol = "<i class=\"fa-solid fa-question\"></i>";
        symbol = eventsubs_status[es_status];
        tr = table.insertRow();
        td1 = tr.insertCell();
        td1.appendChild(document.createTextNode(es_type));
        td2 = tr.insertCell();
        td2.innerHTML = symbol;
    }
    updateDiv = document.getElementById("eventsubs-updatetime-ms");
    updateDiv.value = Date.now();
    e.innerHTML = "<i class=\"fa-solid fa-circle-check status-gif\"></i>";
}


async function updateChatters(){
    var e = document.getElementById("topchatters-status-img");
    e.innerHTML = "<img src=\"img/status.gif\" class=\"status-gif\">";

    //Fetch Raids
    let response = await fetch("status.php?card=topchatters");
    let json = await response.json();

    var table = document.getElementById("topchatters-table");
    table.innerHTML = "";

    const tr = table.insertRow();
    const td1 = tr.insertCell();
    td1.appendChild(document.createTextNode("User"));
    const td2 = tr.insertCell();
    td2.appendChild(document.createTextNode("Msgs"));

    for(let i = 0; i < json["topchatters"].length; i++){
        const badges = json["topchatters"][i]["badges"];
        const username = json["topchatters"][i]["username"];
        const msg_cnt = json["topchatters"][i]["msg_cnt"];
        var badges_arr = badges.split("|");
        var badges_str = "";
        for(let x = 0; x < badges_arr.length; x++){
            if(badges_arr[x] == "BROADCASTER"){
                badges_str += badge_json["BROADCASTER"];
                break;
            }
            if(badges_arr[x] == "VIP"){
                badges_str += badge_json["VIP"];
            }
            if(badges_arr[x] == "SUB"){
                badges_str += badge_json["SUB"];
            }
            if(badges_arr[x] == "SUB2"){
                badges_str += badge_json["SUB2"];
            }
            if(badges_arr[x] == "SUB3"){
                badges_str += badge_json["SUB3"];
            }
            if(badges_arr[x] == "MOD"){
                badges_str += badge_json["MOD"];
            }
        }
        const tr = table.insertRow();
        const td1 = tr.insertCell();
        td1.innerHTML = badges_str + " " + username;
        const td2 = tr.insertCell();
        td2.appendChild(document.createTextNode(msg_cnt));
    }

    e.innerHTML = "<i class=\"fa-solid fa-circle-check status-gif\"></i>";
    updateDiv = document.getElementById("topchatters-updatetime-ms");
    updateDiv.value = Date.now();
}

async function updateRaids(){
    var e = document.getElementById("raids-status-img");
    e.innerHTML = "<img src=\"img/status.gif\" class=\"status-gif\">";

    //Fetch Raids
    let response = await fetch("status.php?card=raid");
    let json = await response.json();

    raidTable = document.getElementById("raid-table");
    raidTable.innerHTML = "";

    const tr = raidTable.insertRow();
    const td1 = tr.insertCell();
    td1.appendChild(document.createTextNode("Raider"));
    const td2 = tr.insertCell();
    td2.appendChild(document.createTextNode("Viewers"));

    for(let i = 0; i < json["raid"].length; i++){
        const tr = raidTable.insertRow();
        const td1 = tr.insertCell();
        td1.appendChild(document.createTextNode(json["raid"][i]["user_name"]));
        const td2 = tr.insertCell();
        td2.appendChild(document.createTextNode(json["raid"][i]["viewers"]));
    }

    e.innerHTML = "<i class=\"fa-solid fa-circle-check status-gif\"></i>";
    updateDiv = document.getElementById("raids-updatetime-ms");
    updateDiv.value = Date.now();
}