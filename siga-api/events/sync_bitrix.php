<?php

include_once __DIR__."/../constants.php";
include_once __DIR__."/../v1/classes/class.bitrix.php";
include_once __DIR__."/queue_handler.php";

function fill_queue_with_hireds(){
    global $wpdb;

    $bitrix = new Bitrix();

    $query_consultores = "SELECT ID FROM wp_posts WHERE ID IN (SELECT post_id FROM wp_postmeta WHERE meta_key = 'c_status' AND meta_value = 'contratado') AND ID IN (SELECT post_id FROM wp_postmeta WHERE meta_key = 'c_vaga' AND meta_value = 'consultor')";
    $query_supervisor = "SELECT ID FROM wp_posts WHERE ID IN (SELECT post_id FROM wp_postmeta WHERE meta_key = 'c_status' AND meta_value = 'contratado') AND ID IN (SELECT post_id FROM wp_postmeta WHERE meta_key = 'c_vaga' AND meta_value = 'supervisor')";
    $query_directors = "SELECT ID FROM wp_posts WHERE ID IN (SELECT post_id FROM wp_postmeta WHERE meta_key = 'c_status' AND meta_value = 'contratado') AND ID IN (SELECT post_id FROM wp_postmeta WHERE meta_key = 'c_vaga' AND meta_value = 'diretor')";
    $query_manager = "SELECT ID FROM wp_posts WHERE ID IN (SELECT post_id FROM wp_postmeta WHERE meta_key = 'c_status' AND meta_value = 'contratado') AND ID IN (SELECT post_id FROM wp_postmeta WHERE meta_key = 'c_vaga' AND meta_value = 'gerente')";
    $query_lider = "SELECT ID FROM wp_posts WHERE ID IN (SELECT post_id FROM wp_postmeta WHERE meta_key = 'c_status' AND meta_value = 'contratado') AND ID IN (SELECT post_id FROM wp_postmeta WHERE meta_key = 'c_vaga' AND meta_value = 'lider')";

    $queue_consultores = array_column($wpdb->get_results($query_consultores), "ID");
    $queue_supervisor = array_column($wpdb->get_results($query_supervisor), "ID");
    $queue_directors = array_column($wpdb->get_results($query_directors), "ID");
    $queue_manager = array_column($wpdb->get_results($query_manager), "ID");
    $queue_lider = array_column($wpdb->get_results($query_lider), "ID");
    
    $queue = [...$queue_directors, ...$queue_lider, ...$queue_manager, ...$queue_supervisor, ...$queue_consultores];

    if (sizeof($queue) == 0) return;
    
    foreach ($queue as $id) {
        $success = add_to_queue(intval($id), SCHEDULE_SYNC_BITRIX_SLUG, ["status" => "unsynced"]);

        if (!$success) {
            $bitrix->create_log("error until insert ".$id." into queue ".SCHEDULE_SYNC_BITRIX_SLUG);
        }
    }
}

function sync_bitrix(int $post_per_sync = 1){
    $bitrix = new Bitrix(true);
    $queue = get_queue(SCHEDULE_SYNC_BITRIX_SLUG, $post_per_sync);
    
    if (sizeof($queue) == 0){
        fill_queue_with_hireds();
        return;
    }
    
    foreach ($queue as $candidate) {
        $id = intval($candidate["id"]);
        $status = $candidate["args"]["status"];

        if ($status == "synced") {
            remove_from_queue($id, SCHEDULE_SYNC_BITRIX_SLUG);
            return;
        }

        try {
            $bitrix->create_log(($id));
            update_in_queue($id, SCHEDULE_SYNC_BITRIX_SLUG, ["status" => "syncing"]);
            //$result = $bitrix->sync_candidate($id);
            $bitrix->create_log("Candidate with id $id synqued");
            
            update_in_queue($id, SCHEDULE_SYNC_BITRIX_SLUG, ["status" => "synced"]);
        } catch(\throwable $e) {
            $bitrix->create_log("Candidate with id $id not sync\n".((string)$e), "error");
        }
    }
}
