const badge_json = {
    "VIP": "<img src=\"img/badges_vip.png\">",
    "SUB": "<i class=\"fa-solid fa-1\"></i>",
    "SUB2": "<i class=\"fa-solid fa-2\"></i>",
    "SUB3": "<i class=\"fa-solid fa-3\"></i>",
    "MOD": "<img src=\"img/badges_mod.png\">",
    "BROADCASTER": "<i class=\"fa-solid fa-video\"></i>",
};

const eventsubs_status = {
    "enabled": "<i class=\"alive fa-solid fa-circle-check status-gif\"></i>",
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

function updateBotStatus(){
    console.log("updateBotStatus");
    var e = document.getElementById("bot-status-symbol");
    e.innerHTML = "<img src=\"img/status.gif\" class=\"status-gif\">";

    //Fetch Bot Status

    e.innerHTML = "<i class=\"fa-solid fa-circle-check status-gif\"></i>";
}

async function updateEventsubs(){
    console.log("updateEventsubs");
    var e = document.getElementById("eventsubs-status-img");
    e.innerHTML = "<img src=\"img/status.gif\" class=\"status-gif\">";

    //Fetch Event Subs
    let response = await fetch("status.php?card=eventsubs");
    let json = await response.json();
    
    var table = document.getElementById("eventsubs-table");
    table.innerHTML = "";

    const tr = table.insertRow();
    const td1 = tr.insertCell();
    td1.appendChild(document.createTextNode("Event"));
    const td2 = tr.insertCell();
    td2.appendChild(document.createTextNode("Status"));

    console.log(json);

    for(let i = 0; i < json["eventsubs"].length; i++){
        const es_type = json["eventsubs"][i]["type"];
        const es_status = json["eventsubs"][i]["status"];
        let symbol = "<i class=\"fa-solid fa-question\"></i>";
        symbol = eventsubs_status[es_status];
        const tr = table.insertRow();
        const td1 = tr.insertCell();
        td1.appendChild(document.createTextNode(es_type));
        const td2 = tr.insertCell();
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
    //console.log(response.text());
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