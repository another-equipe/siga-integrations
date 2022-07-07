<?php

include __DIR__."/api-controllers/CandidateController.class.php";

class SIGAAPI{
    private $api_prefix;
    private $api_version;
    private $api_secret;

    public function __construct($api_secret, $api_prefix = "siga", $version = 1){
        $this->api_prefix = $api_prefix;
        $this->api_version = $version;
        $this->api_secret = $api_secret;
    }

    public function activate(){
        flush_rewrite_rules();
    }
    
    public function deactivate(){
        flush_rewrite_rules();
    }

    public function add_route($route, $callback, $method = "GET"){
        $allowed_methods = ["GET", "POST", "PUT", "DELETE"];

        if (!in_array($method, $allowed_methods)) {
            WP_Error("invalid_method", "this method is not valid, try: GET, POST, PUT or DELETE");
        }

        register_rest_route($this->api_prefix . '/v' . $this->api_version, $route, array(
            'methods' => $method,
            'callback' => $callback
        ));
    }

    public function authenticate($api_key){
        global $wpdb;

        $key_encripted_request = hash_hmac("sha256", $api_key, $this->api_secret);

        $sql = "SELECT meta_value AS key_encripted FROM wp_postmeta WHERE meta_key = 'key_encripted' AND meta_value = '%s';";
        
        $query = $wpdb->prepare($sql, $key_encripted_request);
        $key_encripted_response = $wpdb->get_results($query)[0]->key_encripted;

        return $key_encripted_request == $key_encripted_response;
    }

    public function route_get_candidates($request){
        try {
            $hasAuthorization = $this->authenticate($request["key"]);
            if ($hasAuthorization) {
                $_SERVER['HTTP_HOST'] + $_SERVER['REQUEST_URI'];

                $candidateController = new CandidateController();

                $count = $candidateController->count_candidates();
                $pagination = $this->get_pagination_config($request["offset"], $request["limit"], $count);
                $candidates = $candidateController->get_candidates($pagination);

                return [
                    "status" => "success",
                    "count" => $count,
                    "next" => $pagination["next_url"],
                    "previous" => $pagination["previous_url"],
                    "candidates" => $candidates
                ];
            }

            return ["status" => "error", "error" => "unauthorized"];
        } catch (Exception $e) {
            return ["status" => "error", "error" => $e];
        }
    }

    public function route_get_candidate_by_phone($request){
        try {
            $hasAuthorization = $this->authenticate($request["key"]);

            if ($hasAuthorization){
                $candidateController = new CandidateController();

                $phone = $request["phone"];
                $candidate = $candidateController->get_candidate_by_phone($phone);
                
                return [
                    "status" => ($candidate == null) ? "not-found" : "success",
                    "candidate" => $candidate
                ];
            }
        } catch (Exception $e) {
            return ["status" => "error", "error" => $e];
        }
    }

    private function get_pagination_config($offset, $limit, $count){
        $pagination = [
            "offset" => intval($offset),
            "limit" => intval($limit),
        ];

        if ($offset == null || $offset < 0){
            $pagination["offset"] = 0;
        }

        if ($limit == null || $limit > 100 || $limit < 1) {
            $pagination["limit"] = 100;
        }

        $uri = strtok($_SERVER['REQUEST_SCHEME']."://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], "?");

        $next = ($pagination["offset"] + $pagination["limit"] >= $count) ? "" : $pagination["offset"] + $pagination["limit"];
        $previous = ($pagination["offset"] - $pagination["limit"]) < 0 ? "" : $pagination["offset"] - $pagination["limit"];
        
        $pagination["next_url"] = (strlen($next) > 0) ? $uri."?offset=".$next."&limit=".$pagination["limit"]."&key=".$_GET['key'] : null;
        $pagination["previous_url"] = (strlen($previous) > 0) ? $uri."?offset=".$previous."&limit=".$pagination["limit"]."&key=".$_GET['key'] : null;

        return $pagination;
    }
}
