<?php 
require_once("log.php");

class Twitch{
	private $client_id = "";
	private $client_secret = "";
	private $redirect_url = "";

	function __construct($client_id, $client_secret, $redirect_url) {
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->redirect_url = $redirect_url;
		$this->token_file = $_SERVER['DOCUMENT_ROOT'] . "/inc/token";
		$this->app_token = $this->read_token_file();
		$this->token_expires_secs = 0;
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

	//Create an App Token (Client Credentials Flow)
	//https://dev.twitch.tv/docs/authentication/getting-tokens-oauth/#client-credentials-grant-flow
	function get_app_token(){
		$data = array(
			"client_id" => $this->client_id,
			"client_secret" => $this->client_secret,
			"grant_type" => "client_credentials"
		);
		$token_url = "https://id.twitch.tv/oauth2/token";
		$r = $this->http_request("POST", $token_url, $data);
		if($r["info"]["http_code"] == 200){
			$obj = json_decode($r["body"], true);
			$this->app_token = $obj["access_token"];
			$this->write_token_file($this->app_token);
			return True;
		}
		$status_code =  $r["info"]["http_code"];
		log_msg("Error getting access token: $status_code");
		return false;
	}

	// Check to see if token is still good
	// If the test request returns 401, get new token
	function check_app_token(){
		//If App Token not initialized, get one
		if($this->app_token == ""){
			$this->get_app_token();
			return;
		}
		$data = false;
		$url = "https://api.twitch.tv/helix/users?login=twitchdev";
		$headers = array("Client-Id: ".$this->client_id, "Authorization: Bearer ".$this->app_token);
		$r = $this->http_request("GET", $url, $data, $headers);
		if($r["info"]["http_code"] == 200){
			// Token is still valid
			return True;
		}
		//Token is not valid, get a new one and write the token file
		$status_code =  $r["info"]["http_code"];
		$this->get_app_token();
	}

	// Fetch List of Current EventSub Subscriptions
	function get_eventsubs(){
		$this->check_app_token();
		$data = false;
		$headers = array("Client-Id: ".$this->client_id, "Authorization: Bearer ".$this->app_token);
		$url = "https://api.twitch.tv/helix/eventsub/subscriptions";
		$r = $this->http_request("GET", $url, $data, $headers);
		if($r["info"]["http_code"] >= 200 && $r["info"]["http_code"] < 300){
			$obj = json_decode($r["body"], true);
			log_msg($r["body"], "app");
			return $obj;
		}
		log_msg("Couldnt get event subs. status code: ".$r["info"]["http_code"], "app");
		return $r;
	}

	// Add EventSub Subscription
	function add_eventsub($arr){
		$this->check_app_token();
		$headers = [
			"Client-Id: ".$this->client_id, 
			"Authorization: Bearer ".$this->app_token,
			"Content-Type: application/json"
		];
		$data = json_encode($arr,JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
		$url = "https://api.twitch.tv/helix/eventsub/subscriptions";
		$r = $this->http_request("POST", $url, $data, $headers);
		if($r["info"]["http_code"] >= 200 && $r["info"]["http_code"] < 300){
			$obj = json_decode($r["body"], true);
			return $obj;
		}
		$status_code =  $r["info"]["http_code"];
		log_msg("Error creating subscripotion: $status_code", "error");
		log_msg($r["body"], "error");
		return false;
	}

	//Delete an EventSub
	// Fetch List of Current EventSub Subscriptions
	function delete_eventsub($id){
		$this->check_app_token();
		$data = false;
		$headers = array("Client-Id: ".$this->client_id, "Authorization: Bearer ".$this->app_token);
		$url = "https://api.twitch.tv/helix/eventsub/subscriptions?id=".$id;
		$r = $this->http_request("DELETE", $url, $data, $headers);
		if($r["info"]["http_code"] >= 200 && $r["info"]["http_code"] < 300){
			$obj = json_decode($r["body"], true);
			log_msg($r["info"]["http_code"]);
			log_msg($r["body"], "app");
			return $obj;
		}
		log_msg("Couldnt delete eventsub. status code: ".$r["info"]["http_code"], "app");
		log_msg($r["body"], "error");
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
			return file_get_contents($this->token_file);
		}
		return "";
	}

	//Utility Function
	//Utility Function to read OAuth token from file
	function write_token_file($token){
		if(file_put_contents($this->token_file, $token) == False){
			log_msg("Error writing token to file\nToken File: $token", "error");
			return False;
		}
		return True;
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
			case "DELETE":
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
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