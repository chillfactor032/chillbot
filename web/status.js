const badge_json = {
    "VIP": "<img src=\"img/badges_vip.png\">",
    "SUB": "<i class=\"fa-solid fa-1\"></i>",
    "SUB2": "<i class=\"fa-solid fa-2\"></i>",
    "SUB3": "<i class=\"fa-solid fa-3\"></i>",
    "MOD": "<img src=\"img/badges_mod.png\">",
    "BROADCASTER": "<i class=\"fa-solid fa-video\"></i>",
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
        //td1.appendChild(document.createTextNode());
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