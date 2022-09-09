<?php

include_once __DIR__."/../constants.php";
include_once __DIR__."/../v1/classes/class.historic.php";
include_once __DIR__."/queue_handler.php";

function send_distract(){
    global $wpdb;

    $result = get_queue(SCHEDULE_SEND_DISTRACT_SLUG);
    
    $id = sizeof($result) > 0 ? $result[0]["id"] : null;
    $post = get_post(intval($id));
    $name = is_null($post) ? null : $post->post_title;
    
    if (is_null($id)) return;
    
    $Historic = new Historic();

    remove_from_queue(intval($id), SCHEDULE_SEND_DISTRACT_SLUG);

    try {
        $url = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['HTTP_HOST']."/adapters/adapter_send_contract.php";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(["id" => intval($id), "contract_model" => SAVESIGN_DISTRACT_MODEL_ID]));

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
    }
}