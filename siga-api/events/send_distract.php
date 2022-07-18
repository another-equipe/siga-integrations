<?php

include_once __DIR__."/../../../../siga_distrato.php";

function send_distract(){
    global $wpdb;

    $sql_select_id = "SELECT post_title FROM wp_posts WHERE post_type = 'cron_send_distract' LIMIT 1";
    $sql_delete_post = "DELETE FROM wp_posts WHERE post_type = 'cron_send_distract' AND post_title = '%d' LIMIT 1";
    $id = $wpdb->get_results($sql_select_id)[0]->post_title;

    if (function_exists("distratarUsers") && !is_null($id)){
        $query_delete_post = $wpdb->prepare($sql_delete_post, intval($id));
        $wpdb->get_results($query_delete_post);
        distratarUsers($id);
    }
}