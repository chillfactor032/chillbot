<?php
$root = $_SERVER["DOCUMENT_ROOT"];
$config_path = $root . "/inc/config.json";

// Read the JSON file 
$json = file_get_contents($config_path);

// Decode the JSON file
$config = json_decode($json,true);

?>