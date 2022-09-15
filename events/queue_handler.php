<?php

function add_to_queue($value, string $queue_name, ?array $args = []): bool{
    return (bool) wp_insert_post(["post_title" => $value, "post_type" => $queue_name, "post_content" => serialize($args)]);
}

function remove_from_queue($value, string $queue_name): bool {
    global $wpdb;

    $sql = "DELETE FROM wp_posts WHERE post_title = '%s' AND post_type = '%s'";
    $query = $wpdb->prepare($sql, $value, $queue_name);

    return (bool) $wpdb->get_results($query);
}

function update_in_queue($value, string $queue_name, $value_to_update): bool{
    global $wpdb;

    $sql = "UPDATE wp_posts SET post_content = '%s' WHERE post_type = '%s' AND post_title = '%d'";
    $query = $wpdb->prepare($sql, serialize($value_to_update), $queue_name, $value);

    return (bool) $wpdb->get_results($query);
}

function get_queue(string $queue_name, ?int $limit = 1): ?array{
    global $wpdb;

    $sql = "SELECT post_title, post_content FROM wp_posts WHERE post_type = '%s' LIMIT %d";
    $query = $wpdb->prepare($sql, $queue_name, $limit ?? 1);

    return array_map(function($value){
        return ["id" => $value->post_title, "args" => unserialize($value->post_content)];
    }, $wpdb->get_results($query));
}