<?php

include_once __DIR__."/../constants.php";
include_once __DIR__."/../v1/classes/class.historic.php";
include_once __DIR__."/queue_handler.php";

function send_distract(){
    $result = get_queue(SCHEDULE_SEND_DISTRACT_SLUG);
    
    $id = sizeof($result) > 0 ? $result[0]["id"] : null;
    
    remove_from_queue(intval($id), SCHEDULE_SEND_DISTRACT_SLUG);

    if (is_null($id)) return;

    try {
        $url = "https://webhook.site/d2150ed1-ad75-43f1-83b0-8f4bf655e042";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(["id" => intval($id)]));

        $test = curl_exec($curl);
        curl_close($curl);

        $url = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['HTTP_HOST']."/adapters/adapter_send_contract.php";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
            "id" => intval($id),
            "contract_model" => SAVESIGN_DISTRACT_MODEL_ID,
            "is_distract" => true
        ]));

        $resp = curl_exec($curl);
        curl_close($curl);

        /* clicksign - temporario */
        $url = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['HTTP_HOST']."/adapters/adapter_send_distract.php";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(["id" => intval($id)]));

        curl_exec($curl);
        curl_close($curl);
    } catch (\throwable $e){
        error_log(((string)$e));
    }
}

send_distract();