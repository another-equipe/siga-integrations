<?php

include_once __DIR__."/../../constants.php";

class ClickSign{
    public function schedule_send_distract(int $id): bool{
        if (is_null($id)) return false;
        $post_arr = [
            "post_title" => $id,
            "post_type" => SCHEDULE_SEND_DISTRACT_SLUG
        ];
        
        $post_id = wp_insert_post($post_arr);
        
        add_post_meta($post_id, "queue_name", SCHEDULE_SEND_DISTRACT_SLUG);

        return $post_id != 0;
    }
    
    public function schedule_send_SPC_contract(int $id): bool{
        if (is_null($id)) return false;
        $post_arr = [
            "post_title" => $id,
            "post_type" => SCHEDULE_SEND_SCP_CONTRACT_SLUG
        ];
        
        $post_id = wp_insert_post($post_arr);
        
        add_post_meta($post_id, "queue_name", SCHEDULE_SEND_SCP_CONTRACT_SLUG);

        return $post_id != 0;
    }

    public function schedule_send_attachment_contract(int $id): bool{
        if (is_null($id)) return false;
        $post_arr = [
            "post_title" => $id,
            "post_type" => SCHEDULE_SEND_ATTACHMENT_CONTRACT_SLUG
        ];
        
        $post_id = wp_insert_post($post_arr);
        
        add_post_meta($post_id, "queue_name", SCHEDULE_SEND_ATTACHMENT_CONTRACT_SLUG);

        return $post_id != 0;
    }
}