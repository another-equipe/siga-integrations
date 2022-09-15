<?php

include_once __DIR__."./../../utils/sanitize_metas.php";

class AcademiaController {
    private $domain;
    private $access_token;
    private $client_id;

    public function __construct($domain, $client_id, $access_token){
        $this->domain = $domain;
        $this->access_token = $access_token;
        $this->client_id = $client_id;
    }

    public function create_user($candidate, $pass){

        $data = [
            'username' => $candidate["candidate_nome"],
            'password' => $pass,
            'email' => $candidate["candidate_email"]
        ];

        $result = $this->learnworldsPostRequest('/users', $this->access_token, $this->client_id, $data);

        return ($result['success']) ? $result : false;
    }

    private function learnworldsPostRequest($uri, $token, $client_id, $data){

        $post = ['data' => json_encode($data)];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->domain . $uri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_HTTPHEADER => [
                "authorization: Bearer {$token}",
                "lw-client: {$client_id}",
            ],
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        return ($err) ? ["success" => false] : json_decode($response, true);
    }
}