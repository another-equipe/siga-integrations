<?php

define("DEFAULT_API_PREFIX", "siga");
define("DEFAULT_API_VERSION", 1);

class SIGAAPI{
    private $api_prefix;
    private $api_version;

    public function __construct($api_prefix = DEFAULT_API_PREFIX, $version = DEFAULT_API_VERSION){
        $this->api_prefix = $api_prefix;
        $this->api_version = $version;
    }

    public function activate(){
        flush_rewrite_rules();
    }
    
    public function deactivate(){
        flush_rewrite_rules();
    }

    public function add_route($route, $callback, $method = "GET"){
        $methods_options = ["GET", "POST", "PUT", "DELETE"];

        register_rest_route($this->api_prefix . '/v' . $this->api_version, $route, array(
            'methods' => (in_array($method, $methods_options)) ? $method : "GET",
            'callback' => $callback
        ), true);
    }
}