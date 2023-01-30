function songTableAddRow(tableId, rowNum){
	var table = document.getElementById(tableId);
	if(rowNum < 0 || rowNum >= table.rows.length) return;
	
	songTableAppendEmptyRow(tableId);
	
	var row;
	var tm,name,req,pr;
	for (var i = table.rows.length-2; i > rowNum; i--) {
		tm = document.getElementById("song"+(i-1)+"-tm");
		name = document.getElementById("song"+(i-1)+"-name");
		req = document.getElementById("song"+(i-1)+"-req");
		pr = document.getElementById("song"+(i-1)+"-pr");
		
		tm2 = document.getElementById("song"+i+"-tm");
		name2 = document.getElementById("song"+i+"-name");
		req2 = document.getElementById("song"+i+"-req");
		pr2 = document.getElementById("song"+i+"-pr");
		
		tm2.checked = tm.checked;
		name2.value = name.value;
		req2.value = req.value;
		pr2.checked = pr.checked;
	}
	songTableClearRow(tableId, rowNum+1);
}

function songTableAppendEmptyRow(tableId){
	var table = document.getElementById(tableId);
	
	var newRowNum = table.rows.length-1;
	var row = table.insertRow();
	var numCell = row.insertCell(0);
	var tmCell = row.insertCell(1);
	var nameCell = row.insertCell(2);
	var reqCell = row.insertCell(3);
	var prCell = row.insertCell(4);
	var editCell = row.insertCell(5);
	
	numCell.innerHTML = (newRowNum+1) + ":";
	numCell.style = "text-align: right;";
	tmCell.innerHTML = "<input type=\"checkbox\" id=\"song"+newRowNum+"-tm\" name=\"songs["+newRowNum+"][tm]\" value=\"tm\">";
	tmCell.style = "text-align: center;";
	nameCell.innerHTML = "<input type=\"text\" id=\"song"+newRowNum+"-name\" name=\"songs["+newRowNum+"][name]\" value=\"\">";
	reqCell.innerHTML = "<input type=\"text\" id=\"song"+newRowNum+"-req\" name=\"songs["+newRowNum+"][req]\" value=\"\">";
	prCell.innerHTML = "<input type=\"checkbox\" id=\"song"+newRowNum+"-pr\" name=\"songs["+newRowNum+"][pr]\" value=\"pr\">";
	prCell.style = "text-align: center;";
	editCell.innerHTML += "<a href=\"#\" onclick=\"songTableAddRow('song-table', "+newRowNum+")\"><img src=\"img/ico_add.png\" style=\"width:20px; height: auto;\" title=\"Add Row Below\"></a>\r\n";
	editCell.innerHTML += "<a href=\"#\" onclick=\"songTableRemoveRow('song-table', "+newRowNum+")\"><img src=\"img/ico_del.png\" style=\"width:20px; height: auto;\" title=\"Remove This Row\"></a>";
}

function songTableRemoveRow(tableId, rowNum){
	var table = document.getElementById(tableId);
	
	if(rowNum < 0 || rowNum >= table.rows.length) return;
	
	//clear the row, shift all the cell data down, remove the last row
	songTableClearRow(tableId, rowNum);
	var row;
	var tm,name,req,pr;
	for (var i = rowNum; i < table.rows.length-2; i++) {
		tm = document.getElementById("song"+i+"-tm");
		name = document.getElementById("song"+i+"-name");
		req = document.getElementById("song"+i+"-req");
		pr = document.getElementById("song"+i+"-pr");
		
		tm.checked = document.getElementById("song"+(i+1)+"-tm").checked;
		name.value = document.getElementById("song"+(i+1)+"-name").value;
		req.value = document.getElementById("song"+(i+1)+"-req").value;
		pr.checked = document.getElementById("song"+(i+1)+"-pr").checked;
	}

	if(table.rows.length>1){
		table.deleteRow(table.rows.length-1)
	}
}

function songTableClearRow(tableId, rowNum){
	if(rowNum < 0) return;
	var table = document.getElementById(tableId);
	document.getElementById("song"+rowNum+"-tm").checked = false;
	document.getElementById("song"+rowNum+"-name").value = "";
	document.getElementById("song"+rowNum+"-req").value = "";
	document.getElementById("song"+rowNum+"-pr").checked = false;
}

function sendDiscord(){
	console.log("Sending Discord Notification");
	var result;
	var vote_id = document.getElementById("vote_id").value;
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if(this.readyState == 4){
            if(this.status >= 200 && this.status < 400) {
                result = this.responseText
                console.log("Notification Sent!");
            }else{
                console.log("Discord sent and error back: "+result);
            }
		}
	};
	xhttp.open("GET", "discord.php?vote="+vote_id, true);
	xhttp.send();
}

function getVoteUpdate(){
	console.log("Getting Vote Update");
	var result;
	var votes;
	var total = 0;
	var cell;
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if(this.readyState == 4 && this.status == 200) {
			result = JSON.parse(this.responseText);
			votes = result["data"];
			var total = 0;
			var cell;
			var max = 0;
			var winners = new Array();
			for(i = 0; i < votes.length; i++){
				if(votes[i]["pr"] > 0){
					continue;
				}
				
				if(votes[i]["vote_cnt"] > max){
					max = votes[i]["vote_cnt"]
				}
				cell = document.getElementById("vote-"+votes[i]["ballot_num"]);
				cell.innerHTML = votes[i]["vote_cnt"];
				total += parseInt(votes[i]["vote_cnt"]);
			}
			cell = document.getElementById("vote-total");
			cell.innerHTML = total;
			
			//Find the winner rows and bold them
			for(i = 0; i < votes.length; i++){
				cell = document.getElementById("row-"+votes[i]["ballot_num"]);
				if(votes[i]["vote_cnt"] == max){
					cell.className = "winner-row";
				}else{
					cell.className = "";
				}
			}
		}
	};
	xhttp.open("GET", "vote.php?task=monitor", true);
	xhttp.send();

}

function loadCandidatesLS(){
	var ls_json = localStorage.getItem("state");
	if(ls_json == null){
		console.log("No State Value");
		return;
	}
	var ls;
	try{
		ls = JSON.parse(ls_json);
	}catch(e){
		//bad json
		console.log("Bad State JSON, clearing LocalStorage state");
		localStorage.removeItem("state");
		return;
	}
	console.log("Loading Vote State: " + JSON.stringify(ls));
	
	

	//Set the values of the table from Local Storage
	for(var i = 0; i < ls["candidates"].length; i++){
		if(i >= 5){
			//Add table rows if necessary
			songTableAppendEmptyRow("song-table");
		}
		document.getElementById("song"+i+"-name").value = ls["candidates"][i]["name"];
		document.getElementById("song"+i+"-req").value = ls["candidates"][i]["req"];
		document.getElementById("song"+i+"-tm").checked = ls["candidates"][i]["tm"];
		document.getElementById("song"+i+"-pr").checked = ls["candidates"][i]["pr"];
	}
}

function saveCandidatesLS(){
	var table = document.getElementById("song-table");
	var candidates = new Array();
	var numrows = table.rows.length-1;
	var ballot = 0;
	var name = "";
	var req = "";
	var tm = false;
	var pr = false;
	
	for(var i = 0; i < numrows; i++){
		ballot = i+1;
		name = document.getElementById("song"+i+"-name").value;
		req = document.getElementById("song"+i+"-req").value;
		tm = document.getElementById("song"+i+"-tm").checked;
		pr = document.getElementById("song"+i+"-pr").checked;
		candidates.push({
			"name": name,
			"req": req,
			"tm": tm,
			"pr": pr,
			"ballot": ballot
		});
	}
	var ls = {"candidates": candidates};
	var ls_json = JSON.stringify(ls);
	console.log("Saving State: " + ls_json);
	localStorage.setItem("state", ls_json);
}






