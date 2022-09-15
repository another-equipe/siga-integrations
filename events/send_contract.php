<?php

include_once __DIR__."/../constants.php";
include_once __DIR__."/queue_handler.php";

function send_distract(){
    global $wpdb;

    $event = get_queue(SCHEDULE_SEND_DISTRACT_SLUG)[0];
    $candidate = get_post(intval($event["id"]));

    if (is_null($event["id"])) return;

    

    remove_from_queue(intval($event["id"]), SCHEDULE_SEND_DISTRACT_SLUG);

/* 
    
    $id = sizeof($result) > 0 ? $result[0]["id"] : null;
    $name = is_null($post) ? null : $post->post_title;
    
    
    $Historic = new Historic();


    try {
        $url = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['HTTP_HOST']."/adapters/adapter_send_distract.php";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(["id" => intval($id)]));

        $resp = curl_exec($curl);
        curl_close($curl);

        $Historic->new([
            "title" => "[distract sent] - ".$name,
            "action" => "distract sent",
            "who_received" => $name
        ]);
    } catch (\throwable $e){
        error_log(((string)$e));

        $Historic->new([
            "title" => "[error][distract sent] - ".$name,
            "action" => "distract sent",
            "who_received" => $name
        ]);
    } */
}