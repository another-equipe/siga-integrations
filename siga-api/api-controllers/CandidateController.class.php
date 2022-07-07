<?php

class CandidateController {
    public function get_candidates($pagination = ["offset" => 0, "limit" => 100]){
        global $wpdb;

        $sql = "
        SELECT 
            ID, 
            post_title 
        FROM 
            wp_posts 
        WHERE ID IN (
            SELECT 
                DISTINCT post_id
            FROM 
                wp_postmeta 
            WHERE 
                meta_key = 'c_status'
                AND meta_value = 'contratado' 
                OR meta_value = 'demitido'
            ) 
        LIMIT 
            %d, %d;
        ";
        $query = $wpdb->prepare($sql, $pagination["offset"], $pagination["limit"]);
        $candidates = $wpdb->get_results($query);
        $candidates_posts = [];
    
        foreach ($candidates as $candidate) {
            $post = get_post_meta($candidate->ID);
            $candidate_post = [
                "candidate_nome"        => $candidate->post_title,
                "candidate_email"       => $post["c_email"][0],
                "candidate_vaga"        => $post["c_vaga"][0],
                "candidate_telefone"    => $post["c_telefone"][0],
                "candidate_cpf"         => $post["c_cpf"][0],
                "candidate_cnpj"        => $post["c_cnpj"][0],
                "candidate_razao"       => $post["c_razao"][0],
                "candidate_status"      => $post["c_status"][0],
                "recruiter_nome"        => $post["c_recrutador_nome"][0],
                "recruiter_email"       => $post["c_recrutador"][0],
                "recruiter_telefone"    => $post["c_recrutador_telefone"][0],
                "diretor_nome"          => $post["c_diretor_nome"][0],
                "lider_nome"            => $post["c_lider_nome"][0],
                "gerente_nome"          => $post["c_gerente_nome"][0],
                "supervisor_nome"       => $post["c_supervisor_nome"][0],
            ];
    
            array_push($candidates_posts, $candidate_post);
        }

        return $candidates_posts;
    }

    public function get_candidate_by_phone($phone){
        global $wpdb;
        
        $sanitized_phone = str_replace(['(', ')', '-', ' ', '.', '/'], '', $phone);
        
        $sql = "
        SELECT 
            ID, 
            post_title 
        FROM 
            wp_posts 
        WHERE ID = (
            SELECT 
                post_id
            FROM 
                wp_postmeta 
            WHERE 
                meta_key = 'c_telefone'
                AND meta_value LIKE '%s'
            LIMIT 1
            )
        ";
        $query = $wpdb->prepare($sql, $sanitized_phone);
        $candidate = $wpdb->get_results($query)[0];

        $post = get_post_meta($candidate->ID);

        $candidate_post = ($candidate == null) ? null : [
            "candidate_nome"        => $candidate->post_title,
            "candidate_email"       => $post["c_email"][0],
            "candidate_vaga"        => $post["c_vaga"][0],
            "candidate_telefone"    => $post["c_telefone"][0],
            "candidate_cpf"         => $post["c_cpf"][0],
            "candidate_cnpj"        => $post["c_cnpj"][0],
            "candidate_razao"       => $post["c_razao"][0],
            "candidate_status"      => $post["c_status"][0],
            "recruiter_nome"        => $post["c_recrutador_nome"][0],
            "recruiter_email"       => $post["c_recrutador"][0],
            "recruiter_telefone"    => $post["c_recrutador_telefone"][0],
            "diretor_nome"          => $post["c_diretor_nome"][0],
            "lider_nome"            => $post["c_lider_nome"][0],
            "gerente_nome"          => $post["c_gerente_nome"][0],
            "supervisor_nome"       => $post["c_supervisor_nome"][0],
        ];
        
        return $candidate_post;
    }

    public function count_candidates(){
        global $wpdb;

        $sql = "
            SELECT 
                COUNT(DISTINCT post_id) AS count
            FROM 
                wp_postmeta 
            WHERE 
                meta_key = 'c_status'
                AND meta_value = 'contratado' 
                OR meta_value = 'demitido'
        ";
        $count = $wpdb->get_results($sql)[0]->count;

        return intval($count);
    }
}