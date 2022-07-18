<?php

include_once __DIR__."./../../utils/sanitize_metas.php";

define("CANDIDATES_SIGNS_SLUG", "savesign_candidatos");

class SignsController {
    public function get_candidates_signs($pagination = ["offset" => 0, "limit" => 100], FilterController $filter_controller){
        global $wpdb;

        $sql = "
        SELECT 
            ID, 
            post_title 
        FROM 
            wp_posts 
        WHERE 
            post_type = '%s'
            AND ID IN (
                SELECT
                    DISTINCT post_id
                FROM 
                    wp_postmeta
                WHERE 1
            )
        LIMIT 
            %d, %d;
        ";

        $query = $wpdb->prepare($sql, CANDIDATES_SIGNS_SLUG, $pagination["offset"], $pagination["limit"]);
        $posts = $wpdb->get_results($query);
        $response_data = [];
    
        foreach ($posts as $candidate_sign) {
            $post = get_post_meta($candidate_sign->ID);
            $post = sanitize_metas($post);

            array_push($response_data, [
                "id" => $candidate_sign->ID,
                "name" => $candidate_sign->post_title,
                "cpf" => $post["sign_cpf"],
                "email" => $post["sign_email"],
                "telefone" => $post["sign_telefone"],
                "recrutador" => $post["sign_recrutador"],
                "contrato" => $post["sign_contrato"],
                "assinatura" => $post["sign_assinatura"]
            ]);
        }

        return $response_data;
    }

    public function get_candidate_sign($id){
        global $wpdb;
        
        $sql = "SELECT ID, post_title FROM wp_posts WHERE ID = %d";
        $query = $wpdb->prepare($sql, $id);
        $candidate_sign = $wpdb->get_results($query)[0];

        $post = get_post_meta($candidate_sign->ID);
        $post = sanitize_metas($post);

        $post_data_sanitized = ($post == null) ? null : [
            "id" => $candidate_sign->ID,
            "name" => $candidate_sign->post_title,
            "cpf" => $post["sign_cpf"],
            "email" => $post["sign_email"],
            "telefone" => $post["sign_telefone"],
            "recrutador" => $post["sign_recrutador"],
            "contrato" => $post["sign_contrato"],
            "assinatura" => $post["sign_assinatura"]
        ];

        
        return $post_data_sanitized;
    }

    public function count(){
        global $wpdb;

        $sql = "
        SELECT 
            COUNT(ID) AS count
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
                    meta_key = 'c_status' 
                    AND meta_value = 'contratado' 
                    OR meta_value = 'distratado'        
            )
        ";
        $query = $wpdb->prepare($sql, CANDIDATES_SIGNS_SLUG);
        $count = $wpdb->get_results($query)[0]->count;

        return intval($count);
    }

    public function send_distract($candidate_id){
        
    }
}