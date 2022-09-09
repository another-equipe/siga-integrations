<?php

include_once __DIR__."/../constants.php";
include_once __DIR__."/queue_handler.php";



function fill_queue_with_webhook_events(){
    global $wpdb;

    $sql = "
    SELECT
        ID
    FROM
        wp_posts
    WHERE
        post_type = '%s'
        AND ID IN (
            SELECT
                DISTINCT post_id
            FROM
                wp_postmeta
            WHERE
                meta_key = 'sc_event_status'
                AND meta_value IN (
                    'waiting',
                    'error',
                    ''
                )
        )
    ";
    $query = $wpdb->prepare($sql, WEBHOOKS_SLUG);
    $result = $wpdb->get_results($query);

    if (sizeof($result) > 0) {
        foreach ($result as $post) {
            add_to_queue(
                intval($post->ID),
                SCHEDULE_WEBHOOKS,
                [
                    "URL" => WEBHOOK_URL,
                    "data" => [
                        "data" => get_post_meta(intval($post->ID), "sc_data", true),
                        "trigger" => get_post_meta(intval($post->ID), "sc_trigger", true)
                    ]
                ]
            );
        }
    }
}

function send_webhook_event(){
    $queue = get_queue(SCHEDULE_WEBHOOKS);
    
    if (sizeof($queue) == 0){
        fill_queue_with_webhook_events();
        return;
    }
    
    foreach ($queue as $value) {
        $id = intval($value["id"]);
        $event = $value["args"];

        try {
			$url = $event["URL"];

			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

			$headers = array(
				"Content-Type: application/json",
			);
			
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            $json = json_encode($event["data"], JSON_UNESCAPED_UNICODE);
            $encripted_data = hash_hmac("sha256", $json, SECRET_KEY_SC);

			curl_setopt($curl, CURLOPT_POSTFIELDS, $json);

			$result = curl_exec($curl);

            update_post_meta($id, "sc_response_return", (string)$result);
            update_post_meta($id, "sc_event_status", "sent");

            curl_close($curl);

            remove_from_queue($id, SCHEDULE_WEBHOOKS);
        } catch(\throwable $e) {
            error_log((string)$e);
        }
    }
}