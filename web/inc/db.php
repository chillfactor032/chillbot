<?php
class Database{
	private $db_host = "";
	private $db_user = "";
	private $db_pass = "";
	private $db_name = "";

	private $conn = NULL;
	private $error = "";

	function __construct($config) {
		$this->db_host = $config["host"];
		$this->db_user = $config["user_name"];
		$this->db_pass = $config["password"];
		$this->db_name = $config["db"];
		$this->conn = NULL;
		$this->error = "";
	}

	function __destruct() {
		if($this->conn){
			$this->conn->close();
		}
	}
	
	function connect(){
		$this->conn = new mysqli($this->db_host, $this->db_user, $this->db_pass, $this->db_name);
		if($this->conn->connect_error){
		  return false;
		}
		return true;
	}

	function is_connected(){
		if($this->conn){
			return $this->conn->ping();
		}
		return false;
	}

	// Insert a raid
	function add_raid($user_name, $user_id, $viewers){
		$result = false;
		$stmt = $this->conn->prepare("INSERT INTO raids(user_name, user_id, viewers) VALUES (?, ?, ?);");
		$stmt->bind_param("sii", $user_name, $user_id, $viewers);
		$result = $stmt->execute();
		if(!$result){
			//echo($this->conn->error);
		}
		$stmt->close();
		return $result;
	}

	//Fetch the raids
	function get_raids($days=1){
		if(!$this->is_connected()) return -1;
		$sql = "SELECT * FROM raids WHERE timestamp >= DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL ? DAY);";
		$stmt = $this->conn->prepare($sql); 
		$stmt->bind_param("i", $days);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows > 0){
			$rows = $result->fetch_all(MYSQLI_ASSOC);
			return $rows;	
		}
		return [];
	}

	//Fetch the top chatters
	function top_chatters(){
		if(!$this->is_connected()) return [];
		$sql = "SELECT username, badges, COUNT(id) as msg_cnt FROM chat WHERE timestamp >= DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY) GROUP BY username ORDER BY msg_cnt DESC LIMIT 5;";
		$result = $this->conn->query($sql);
		if($result->num_rows > 0){
			$rows = $result->fetch_all(MYSQLI_ASSOC);
			return $rows;	
		}
		return [];
	}

	//Fetch the current active vote, or return 0 if none.
	function current_vote(){
		if(!$this->is_connected()) return -1;
		$sql = "SELECT MAX(id) as current_vote FROM vote WHERE status = 'open';";
		$result = $this->conn->query($sql);
		if($result->num_rows > 0){
			$row = $result->fetch_assoc();
			return $row["current_vote"];
		}
		return 0;
	}

	//Fetch the current active vote, or return 0 if none.
	function prev_vote(){
		if(!$this->is_connected()) return -1;
		$sql = "SELECT MAX(id) as prev_vote FROM vote WHERE status = 'closed';";
		$result = $this->conn->query($sql);
		if($result->num_rows > 0){
			$row = $result->fetch_assoc();
			return $row["prev_vote"];
		}
		return 0;
	}

	//Create a new poll and set its status to "started"
	function create_vote(){
		if(!$this->is_connected()) return -1;
		//Check to make sure a vote is not already in progress
		$cur_vote = $this->current_vote($this->conn);
		if($cur_vote > 0){
			return $cur_vote;
		}
		$sql = "INSERT INTO vote () VALUES ()";
		if($this->conn->query($sql) === TRUE){

		}else{
			return false;
		}

		$cur_vote = $this->current_vote();
		return $cur_vote;
	}

	function cast_ballot($ballot_num){
		$cur_vote = $this->current_vote();
		if($cur_vote == 0 || $ballot_num == $POOL_ROOM_BALLOT) return false;
		$result = false;
		$stmt = $this->conn->prepare("INSERT INTO ballot(vote_id, candidate_id) VALUES (?, (SELECT id FROM candidate WHERE vote_id = ? AND ballot_num = ?));");
		$stmt->bind_param("iii", $cur_vote, $cur_vote, $ballot_num);
		$result = $stmt->execute();
		if(!$result){
			//echo($this->conn->error);
		}
		$stmt->close();
		return $result;
	}

	function insert_candidates($vote_id, $candidates){
		//Prepared Statement Values
		$ballot_num = 0;
		$name = "";
		$requester = "";
		$tm = 0;
		$pr = 0;

		$stmt = $this->conn->prepare("INSERT INTO candidate (vote_id, ballot_num, name, requester, tm, pr) VALUES (?, ?, ?, ?, ?, ?)");
		$stmt->bind_param("iissii", $vote_id, $ballot_num, $name, $requester, $tm, $pr);

		for($i = 0; $i < count($candidates); $i++){
			$ballot_num = $candidates[$i]["ballot_num"];
			$name = $candidates[$i]["name"];
			$requester = $candidates[$i]["req"];
			$tm = $candidates[$i]["tm"];
			$pr = $candidates[$i]["pr"];
			$stmt->execute();
		}
		$stmt->close();
	}

	//Get all candidates from a poll.
	function get_candidates($vote_id, $pool_room = false){
		//Include pool room candidates or not
		if($pool_room){
			$sql = "SELECT * FROM candidate WHERE vote_id = " . $this->conn->real_escape_string($vote_id) . " ORDER BY ballot_num ASC;";
		}else{
			$sql = "SELECT * FROM candidate WHERE vote_id = " . $this->conn->real_escape_string($vote_id) . " AND pr = 0 ORDER BY ballot_num ASC;";
		}

		$result = $this->conn->query($sql);
		
		if ($result->num_rows > 0) {
			$rows = $result->fetch_all(MYSQLI_ASSOC); 
			return $rows;
		}
		return 0;
	}

	function get_votes($vote_id, $order_by_votes=False){
        $order_by_sql = "ORDER BY c.ballot_num ASC";
        if($order_by_votes){
            $order_by_sql = "ORDER BY vote_cnt DESC";
        }
		$vote_id = intval($vote_id);
		$sql = <<<SQL
SELECT c.ballot_num, c.name, c.requester, c.tm, c.pr, COUNT(b.candidate_id) as vote_cnt
FROM candidate as c
LEFT JOIN ballot as b ON b.candidate_id = c.id
	AND b.vote_id = c.vote_id
WHERE c.vote_id = $vote_id
GROUP BY c.ballot_num
$order_by_sql;
SQL;
		$result = $this->conn->query($sql);
		if(!$result){
			echo($this->conn->error);
		}
		if ($result->num_rows > 0) {
			$rows = $result->fetch_all(MYSQLI_ASSOC);
			return $rows;
		}
		return [];
	}

	function close_vote($cur_vote){
		//Only one vote at a time open
		$sql = "UPDATE vote SET status = 'closed', end_time = now() WHERE id = $cur_vote";
		$result = $this->conn->query($sql);
		if(!$result){
			//echo($this->conn->error);
		}else{
			return true;
		}
		return false;
	}

	function vote_status($vote_id){
		//Force vote_id to be an integer
		$vote_id = intval($vote_id);
		$sql = "SELECT status FROM vote WHERE id = " . $vote_id . ";";
		$result = $this->conn->query($sql);
		if ($result->num_rows > 0) {
			$rows = $result->fetch_all(MYSQLI_ASSOC); 
			return $rows[0]["status"];
		}
		return false;
	}
}
?>