<?php
    $log_dir = $_SERVER['DOCUMENT_ROOT'] . "/log";

    $log_files = [
        [
            "name" => "error",
            "path" => $log_dir."/error.log"
        ],[
            "name" => "app",
            "path" => $log_dir."/app.log"
        ],[
            "name" => "eventsub",
            "path" => $log_dir."/eventsub.log"
        ],[
            "name" => "twitch",
            "path" => $log_dir."/twitch.log"
        ]
    ];

    function log_msg($msg, $log = "app"){
        global $log_files;
        $msg .= "\n";
        foreach($log_files as $lf){
            if($lf["name"] == $log){
                if(file_put_contents($lf["path"], $msg, FILE_APPEND) == FALSE){
                    file_put_contents($log_files[0]["path"], $msg, FILE_APPEND);
                }
            }
        }
    }
?>