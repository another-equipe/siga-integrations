<?php

set_time_limit(0);

include_once __DIR__."/../constants.php";
include_once __DIR__."/../v1/classes/class.historic.php";
include_once __DIR__."/queue_handler.php";

function send_scp_contract(){
    global $wpdb;

    $result = get_queue(SCHEDULE_SEND_SCP_CONTRACT_SLUG);

    $id = sizeof($result) > 0 ? $result[0]["id"] : null;

    $post = get_post(intval($id));
    $name = is_null($post) ? null : $post->post_title;

    if (is_null($id)) return;

    $Historic = new Historic();

    remove_from_queue(intval($id), SCHEDULE_SEND_SCP_CONTRACT_SLUG);

    try {
        $url = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['HTTP_HOST']."/adapters/adapter_send_scp_contract.php";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        $data = json_encode(["id" => intval($id)]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        curl_exec($curl);
        curl_close($curl);

        $Historic->new([
            "title" => "[SCP contract sent] - ".$name,
            "action" => "SCP contract sent",
            "who_received" => $name
        ]);
    } catch (\throwable $e){
        error_log(((string)$e));

        $Historic->new([
            "title" => "[error][SCP contract sent] - ".$name,
            "action" => "SCP contract sent",
            "who_received" => $name
        ]);
    }
}