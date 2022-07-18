<?php

/**
 * Plugin Name: SIGA API
 * Version: 2.1.0
 * Plugin URI: 
 * Description: API para integrações com o SIGA
 * Author: Another Equipe
 * Author URI: 
 *
 * @package WordPress
 * @author Another Equipe
 * @since 2.1.0
 */

include_once __DIR__."/v1/core.php";
include_once __DIR__."/scheduled_events.php";
include_once __DIR__."/v1/classes/class.auth_controller.php";
include_once __DIR__."/v1/classes/class.router.php";

DEFINE("SIGA_V1_ROUTES", [
    "get:candidates" => [
        "route" => "/candidates",
        "method" => "GET"
    ],
    "get:candidate" => [
        "route" => "/candidates/(?P<phone>\d+)",
        "method" => "GET"
    ],
    "post:candidate" => [
        "route" => "/candidate",
        "method" => "POST"
    ],
    "get:candidate_signs" => [
        "route" => "/candidates/signs",
        "method" => "GET"
    ],
    "get:candidate_sign" => [
        "route" => "/candidates/signs/(?P<id>\d+)",
        "method" => "GET"
    ],
    "post:create_academia_user" => [
        "route" => "/academia/user/(?P<id>\d+)",
        "method" => "POST"
    ],
    "post:course_completed" => [
        "route" => "/academia/user/course",
        "method" => "POST"
    ],
    "post:certificate_awarded" => [
        "route" => "/academia/user/certificate",
        "method" => "POST"
    ],
    "get:team" => [
        "route" => "/team/(?P<id>\d+)",
        "method" => "GET"
    ]
]);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function register_sigav1_routes(){
    $SIGA_API = new SIGAAPI("siga", 1);
    
    $SIGA_API->add_route(SIGA_V1_ROUTES["get:candidates"]["route"], function($request){
        $auth_controller = new AuthController();
        $have_auth = $auth_controller->authenticate($request["key"]);
        
        try {
            if ($have_auth){
             	$router = new Router();
                return rest_ensure_response($router->get_candidates($request));
            }
            return ["status" => "error", "error" => "unauthorized"];
        } catch (Exception $e){
            return ["status" => "error", "error" => $e];
        } catch (Throwable $e){
            return ["status" => "fatal-error", "error" => (string)$e];
        }
    }, SIGA_V1_ROUTES["get:candidates"]["method"]);
    
    $SIGA_API->add_route(SIGA_V1_ROUTES["get:candidate"]["route"], function($request){
        $auth_controller = new AuthController();
        $router = new Router();
        $have_auth = $auth_controller->authenticate($request["key"]);
        
        try {
            if ($have_auth){
                return rest_ensure_response($router->get_candidate($request));
            }
            return ["status" => "error", "error" => "unauthorized"];
        } catch (Exception $e){
            return ["status" => "error", "error" => $e];
        } catch (Throwable $e){
            return ["status" => "fatal-error", "error" => (string)$e];
        }
    }, SIGA_V1_ROUTES["get:candidate"]["method"]);
    
    $SIGA_API->add_route(SIGA_V1_ROUTES["get:candidate_signs"]["route"], function($request){
        $auth_controller = new AuthController();
        $router = new Router();
        $have_auth = $auth_controller->authenticate($request["key"]);

        try {
            if ($have_auth){
                return rest_ensure_response($router->get_candidates_signs($request));
            }
            return ["status" => "error", "error" => "unauthorized"];
        } catch (Exception $e){
            return ["status" => "error", "error" => $e];
        } catch (Throwable $e){
            return ["status" => "fatal-error", "error" => (string)$e];
        }
    }, SIGA_V1_ROUTES["get:candidate_signs"]["method"]);
    
    $SIGA_API->add_route(SIGA_V1_ROUTES["get:candidate_sign"]["route"], function($request){
        $auth_controller = new AuthController();
        $router = new Router();
        $have_auth = $auth_controller->authenticate($request["key"]);

        try {
            if ($have_auth){
                return rest_ensure_response($router->get_candidate_sign($request));
            }
            return ["status" => "error", "error" => "unauthorized"];
        } catch (Exception $e){
            return ["status" => "error", "error" => $e];
        } catch (Throwable $e){
            return ["status" => "fatal-error", "error" => (string)$e];
        }
    }, SIGA_V1_ROUTES["get:candidate_sign"]["method"]);

    $SIGA_API->add_route(SIGA_V1_ROUTES["post:create_academia_user"]["route"], function($request){
        $auth_controller = new AuthController();
        $router = new Router();
        $have_auth = $auth_controller->authenticate($request["key"]);

        try {
            if ($have_auth){
                return rest_ensure_response($router->create_academia_user($request));
            }
            return ["status" => "error", "error" => "unauthorized"];
        } catch (Exception $e){
            return ["status" => "error", "error" => $e];
        } catch (Throwable $e){
            return ["status" => "fatal-error", "error" => (string)$e];
        }
    }, SIGA_V1_ROUTES["post:create_academia_user"]["method"]);

    $SIGA_API->add_route(SIGA_V1_ROUTES["post:course_completed"]["route"], function($request){
        $auth_controller = new AuthController();
        $router = new Router();
        $have_auth = $auth_controller->authenticate($request["key"]);

        try {
            if ($have_auth){
                return rest_ensure_response($router->on_course_completed($request));
            }
            return ["status" => "error", "error" => "unauthorized"];
        } catch (Exception $e){
            return ["status" => "error", "error" => $e];
        } catch (Throwable $e){
            return ["status" => "fatal-error", "error" => (string)$e];
        }
    }, SIGA_V1_ROUTES["post:course_completed"]["method"]);

    $SIGA_API->add_route(SIGA_V1_ROUTES["post:certificate_awarded"]["route"], function($request){
        $auth_controller = new AuthController();
        $router = new Router();
        $have_auth = $auth_controller->authenticate($request["key"]);

        try {
            if ($have_auth){
                return rest_ensure_response($router->on_certificate_awarded($request));
            }
            return ["status" => "error", "error" => "unauthorized"];
        } catch (Exception $e){
            return ["status" => "error", "error" => $e];
        } catch (Throwable $e){
            return ["status" => "fatal-error", "error" => (string)$e];
        }
    }, SIGA_V1_ROUTES["post:certificate_awarded"]["method"]);

    $SIGA_API->add_route(SIGA_V1_ROUTES["post:candidate"]["route"], function($request){
        $auth_controller = new AuthController();
        $router = new Router();
        $have_auth = $auth_controller->authenticate($request["key"]);

        try {
            if ($have_auth){
                return rest_ensure_response($router->candidate_action($request));
            }
            return ["status" => "error", "error" => "unauthorized"];
        } catch (Exception $e){
            return ["status" => "error", "error" => $e];
        } catch (Throwable $e){
            return ["status" => "fatal-error", "error" => (string)$e];
        }
    }, SIGA_V1_ROUTES["post:candidate"]["method"]);

    $SIGA_API->add_route(SIGA_V1_ROUTES["get:team"]["route"], function($request){
        $auth_controller = new AuthController();
        $router = new Router();
        $have_auth = $auth_controller->authenticate($request["key"]);

        try {
            if ($have_auth){
                return rest_ensure_response($router->get_team($request));
            }
            return ["status" => "error", "error" => "unauthorized"];
        } catch (Exception $e){
            return ["status" => "error", "error" => $e];
        } catch (Throwable $e){
            return ["status" => "fatal-error", "error" => (string)$e];
        }
    }, SIGA_V1_ROUTES["get:team"]["method"]);
}

add_action("rest_api_init", "register_sigav1_routes");

flush_rewrite_rules();