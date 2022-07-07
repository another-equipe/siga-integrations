<?php

/**
 * Plugin Name: SIGA API
 * Version: 1.0.0
 * Plugin URI: 
 * Description: API para integrações com o SIGA
 * Author: Another Equipe
 * Author URI: 
 *
 * @package WordPress
 * @author Another Equipe
 * @since 1.0.0
 */

include __DIR__."/SIGAAPI.class.php";

define("SIGA_API_SECRET", 'hWmYq3t6w9z$C&F)J@NcRfUjXn2r4u7x');

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (class_exists("SIGAAPI")){
    $SIGA_API = new SIGAAPI(SIGA_API_SECRET);

    register_activation_hook( __FILE__, array($SIGA_API, "activate"));
    register_deactivation_hook( __FILE__, array($SIGA_API, "deactivate"));

    add_action('rest_api_init', function(){
        $SIGA_API = new SIGAAPI(SIGA_API_SECRET);
        
        $SIGA_API->add_route("/candidates", function($request){
            global $SIGA_API;
            return rest_ensure_response($SIGA_API->route_get_candidates($request));
        });
        
        $SIGA_API->add_route("/candidates/(?P<phone>.*)", function($request){
            global $SIGA_API;
            return rest_ensure_response($SIGA_API->route_get_candidate_by_phone($request));
        });
    });
}
