<?php

ini_set('max_execution_time', 250);
ini_set("default_socket_timeout", 6000);

include_once __DIR__."/../constants.php";
include_once __DIR__."/../v1/classes/class.bitrix.php";
include_once __DIR__."/queue_handler.php";

function fill_queue_with_hireds(){
    global $wpdb;

    $bitrix = new Bitrix();

    $query_users = "SELECT DISTINCT post_id
    FROM wp_postmeta
    WHERE meta_key = 'c_email'
    AND meta_value IN
        (SELECT user_email
        FROM wp_users
        WHERE ID IN
            (SELECT user_id
            FROM wp_usermeta
            WHERE meta_key = 'u_id_no_bitrix'
                AND meta_value = 0)
        AND ID IN
            (SELECT user_id
            FROM wp_usermeta
            WHERE meta_key = 'u_funcao'
                AND meta_value IN ('lider',
                                'diretor',
                                'gerente',
                                'supervisor',
                                'consultor') ) ) ORDER BY post_id DESC";

    $query_posts = "SELECT 
        ID, 
        post_title 
    FROM 
        wp_posts 
    WHERE ID IN (
        SELECT 
            post_id
        FROM 
            wp_postmeta 
        WHERE 
            meta_key = 'c_status'
            AND meta_value = 'contratado' 
            OR meta_value = 'distratado'
        )
    ORDER BY ID DESC";

    $queue_users = array_column($wpdb->get_results($query_users), "post_id");
    $queue_posts = array_column($wpdb->get_results($query_posts), "ID");
    $queue = array_unique([...$queue_users, ...$queue_posts]);

    if (sizeof($queue) == 0) return;
    
    foreach ($queue as $id) {
        add_to_queue($id, SCHEDULE_SYNC_BITRIX_SLUG);
    }

    if (sizeof($queue) > 0) {
        $bitrix->create_log(sizeof($queue) . " elements added to queue " . SCHEDULE_SYNC_BITRIX_SLUG);
    }
}

function sync_bitrix(){
    $bitrix = new Bitrix(true);
    $queue = get_queue(SCHEDULE_SYNC_BITRIX_SLUG, 1);
    
    if (sizeof($queue) == 0){
        fill_queue_with_hireds();
        return;
    }
    
    foreach ($queue as $value) {
        $id = intval($value["id"]);

        try {
			$url = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . "/wp-json/siga/v1/bitrix?key=A982C97D452CC71FDE02409D6E6A623220C882930C3C13AB7733E61BC9870B6C";

			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

			$headers = array(
				"Content-Type: application/json",
			);
			
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

			$data = json_encode([
				"trigger" => "sync-org-structure",
				"id" => $id,
				"sync_strategy" => "complete",
                "root_department" => DEFAULT_ROOT_DEPARTMENT
			]);

			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

			$result = curl_exec($curl);

            $bitrix->create_log("SYNC RESULT", "ID = $id", false);
            $bitrix->create_log("" . json_encode($result, JSON_UNESCAPED_UNICODE), "info", false);
            $bitrix->create_log("", "----------------------------------", false);

            remove_from_queue($id, SCHEDULE_SYNC_BITRIX_SLUG);

        } catch(\throwable $e) {
            $bitrix->create_log("Error to sync id $id\n".((string)$e), "error");
        }
    }
}
