<?php

function orfao(){
    try {
        $url = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['HTTP_HOST']."/adapters/adapter_orfao.php";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_exec($curl);
        curl_close($curl);
    } catch (\throwable $e){
        error_log(((string)$e));
    }
}