<?php
    date_default_timezone_set('UTC');

    $log_dir = $_SERVER['DOCUMENT_ROOT'] . "/log";

    $log_files = [
        [
            "pretty_name" => "Error Log",
            "name" => "error",
            "path" => $log_dir."/error.log"
        ],[
            "pretty_name" => "Application Log",
            "name" => "app",
            "path" => $log_dir."/app.log"
        ],[
            "pretty_name" => "Event Subscription Log",
            "name" => "eventsub",
            "path" => $log_dir."/eventsub.log"
        ],[
            "pretty_name" => "Twitch API Log",
            "name" => "twitch",
            "path" => $log_dir."/twitch.log"
        ],[
            "pretty_name" => "Chillbot Log",
            "name" => "chillbot",
            "path" => $log_dir."/chillbot.log"
        ]
    ];

    function get_log_names(){
        global $log_files;
        return $log_files;
    }

    function read_log($since_line=0, $log_name="app"){
        global $log_files;
        $json_resp = [
            "last_line" => $since_line,
            "data" => ""
        ];
        $buf = "";
        foreach($log_files as $lf){
            if($lf["name"] == $log_name){
                if(file_exists($lf["path"])){
                    if($file = fopen($lf["path"],"r")){
                        $line_num = -1;
                        while(!feof($file)) {
                            $line_num++;
                            $line = fgets($file);
                            if($line_num >= $since_line){
                                $buf .= $line;
                            }
                        }
                        //If the log file has shrunk in size, get the whole thing next time
                        if($line_num < $since_line){
                            $line_num = 0;
                        }
                        $json_resp["last_line"] = $line_num;
                        $json_resp["data"] = htmlspecialchars($buf);
                        fclose($file);
                        return json_encode($json_resp, JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
                    }else{
                        log_msg("Error reading log file [$log_name]");
                    }
                }
            }
        }
        return json_encode($json_resp, JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
    }

    function log_msg($msg, $log = "app"){
        global $log_files;
        $timestamp = date('Y-m-d H:i:s', time());
        $msg = $timestamp . " - " . $msg . "\n";
        foreach($log_files as $lf){
            if($lf["name"] == $log){
                if(file_put_contents($lf["path"], $msg, FILE_APPEND) == FALSE){
                    file_put_contents($log_files[0]["path"], $msg, FILE_APPEND);
                }
            }
        }
    }
?>