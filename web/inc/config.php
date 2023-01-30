<?php

// Read the JSON file 
$json = file_get_contents('inc/config.json');

// Decode the JSON file
$config = json_decode($json,true);
// print_r($config);
?>