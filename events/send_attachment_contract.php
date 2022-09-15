<?php

include_once __DIR__."/../constants.php";
include_once __DIR__."/../v1/classes/class.historic.php";
include_once __DIR__."/queue_handler.php";

function send_attachment_contract(){
    global $wpdb;

    $result = get_queue(SCHEDULE_SEND_ATTACHMENT_CONTRACT_SLUG);
    
    $id = sizeof($result) > 0 ? $result[0]["id"] : null;
    $post = get_post(intval($id));
    $name = is_null($post) ? null : $post->post_title;
    
    if (is_null($id)) return;
    
    $Historic = new Historic();

    remove_from_queue(intval($id), SCHEDULE_SEND_ATTACHMENT_CONTRACT_SLUG);

    try {
        $url = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['HTTP_HOST']."/adapters/adapter_send_contract.php";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

        $vaga = get_post_meta(intval($id), "c_vaga", true);

        $contract_model = null;

        switch ($vaga) {
            case 'supervisor':
                $contract_model = SAVESIGN_ATTACHMENT_SUPERVISOR_CONTRACT_MODEL_ID;
                break;
            case 'gerente':
                $contract_model = SAVESIGN_ATTACHMENT_MANAGER_CONTRACT_MODEL_ID;
                break;
            case 'lider':
                $contract_model = SAVESIGN_ATTACHMENT_LID_CONTRACT_MODEL_ID;
                break;
            case 'diretor':
                $contract_model = SAVESIGN_ATTACHMENT_DIRECTOR_CONTRACT_MODEL_ID;                
                break;
            default:
                return;
                break;
        }
        
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(["id" => intval($id), "contract_model" => $contract_model]));

        curl_exec($curl);
        curl_close($curl);

        $Historic->new([
            "title" => "[attachment contract for $vaga sent] - ".$name,
            "action" => "attachment contract sent",
            "who_received" => $name
        ]);
    } catch (\throwable $e){
        error_log(((string)$e));

        $Historic->new([
            "title" => "[error][attachment contract sent] - ".$name,
            "action" => "attachment contract sent",
            "who_received" => $name
        ]);
    }
}