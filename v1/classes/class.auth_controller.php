<?php

include_once __DIR__."/../../constants.php";
class AuthController {
    public function authenticate($api_key){
        global $wpdb;

        if (is_null($api_key)) return false;

        $key_encripted_request = hash_hmac("sha256", $api_key, SIGA_API_SECRET);

        $sql = "SELECT meta_value AS key_encripted FROM wp_postmeta WHERE meta_key = 'key_encripted' AND meta_value = '%s';";
        
        $query = $wpdb->prepare($sql, $key_encripted_request);
        $key_encripted_response = $wpdb->get_results($query)[0]->key_encripted;

        return $key_encripted_request == $key_encripted_response;
    }
}