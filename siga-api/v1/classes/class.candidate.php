<?php
/* 
class Candidate {
    public function get_candidate_by_id(int $id): ?array{
        global $wpdb;

        $sql = "SELECT ID, post_title FROM wp_posts WHERE ID = %d";
        $query = $wpdb->prepare($sql, $id);
        $result = $wpdb->get_results($query);

        return (sizeof($result) > 0) ? $this->buildCandidate($result[0]) : null;
    }

    public function get_superior($candidate){

    }

    public function get_superior_recursive($candidate){

    }

    private function buildCandidate(int $id, ?string $post_title): ?array{
        $metas = array_map(function($meta){
            return (sizeof($meta) > 1) ? $meta : $meta[0];
        }, get_post_meta($id));

        $candidate = ["id" => $id, "name" => $post_title];

        foreach ($metas as $key => $value){
            $candidate[$key] = $value;
        }
        
        return $candidate;
    }
} */