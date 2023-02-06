<?php 

class Twitch{
	private $client_id = "";
	private $client_secret = "";
	private $redirect_url = "";

	function __construct($client_id, $client_secret, $redirect_url) {
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->redirect_url = $redirect_url;
		$this->token_file = "token";
		$this->app_token = "";
	}

	function get_oauth_url($state){
		$data = array(
			"client_id" => $this->client_id,
			"redirect_uri" => $this->redirect_url,
			"response_type" => "code",
			"scope" => "user:read:email",
			"force_verify" => "true",
			"state" => $state
		);
		$auth_url = "https://id.twitch.tv/oauth2/authorize";
		return sprintf("%s?%s", $auth_url, http_build_query($data));
	}

	function get_oauth_token($auth_code){
		$data = array(
			"client_id" => $this->client_id,
			"client_secret" => $this->client_secret,
			"code" => $auth_code,
			"scope" => "user:edit",
			"grant_type" => "authorization_code",
			"redirect_uri" => $this->redirect_url
		);

		$token_url = "https://id.twitch.tv/oauth2/token";
		$r = $this->http_request("POST", $token_url, $data);
		if($r["info"]["http_code"] == 200){
			$obj = json_decode($r["body"], true);
			return $obj;
		}
		return false;
	}

	function get_user_info($token){
		$data = false;
		$headers = array("client-id: ".$this->client_id, "Authorization: Bearer ".$token);
		$url = "https://api.twitch.tv/helix/users";
		$r = $this->http_request("GET", $url, $data, $headers);
		if($r["info"]["http_code"] == 200){
			$obj = json_decode($r["body"], true);
			return $obj;
		}
		return $r;
	}

	// Fetch List of Current EventSub Subscriptions
	function get_eventsubs($token){
		$data = false;
		$headers = array("Client-Id: ".$this->client_id, "Authorization: Bearer ".$token);
		$url = "https://api.twitch.tv/helix/eventsub/subscriptions";
		$r = $this->http_request("GET", $url, $data, $headers);
		if($r["info"]["http_code"] == 200){
			$obj = json_decode($r["body"], true);
			return $obj;
		}
		return $r;
	}

	//Utility Function
	//Generate an anti-csrf token
	function get_state($salt = "", $len=16){
		return substr(hash("sha256", $salt . random_int(0,PHP_INT_MAX)), 0, $len);
	}

	//Utility Function
	//Utility Function to read OAuth token from file
	function read_token_file(){
		if(file_exists($this->token_file)){
			$this->app_token = file_get_contents($this->token_file);
		}
	}

	//Utility Function
	//Make HTTP Calls
	function http_request($method, $url, $data = false, $headers = false){
		$curl = curl_init();
		switch ($method)
		{
			case "POST":
				curl_setopt($curl, CURLOPT_POST, 1);
				if ($data)
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				break;
			case "PUT":
				curl_setopt($curl, CURLOPT_PUT, 1);
				break;
			default:
				if ($data)
					$url = sprintf("%s?%s", $url, http_build_query($data));
		}

		curl_setopt($curl, CURLOPT_URL, $url);

		if($headers){
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$body = curl_exec($curl);
		$info = curl_getinfo($curl);
		$result = array("info" => $info, "body" => $body);
		curl_close($curl);
		return $result;
	}
}
?>