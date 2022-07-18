<?php


class ClickSign{
    public function schedule_send_distract(int $id){
        if (is_null($id)) return;
        $post_arr = [
            "post_title" => $id,
            "post_type" => "cron_send_distract"
        ];
        wp_insert_post($post_arr);
    }
}