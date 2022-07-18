<?php

include_once __DIR__."/class.candidate_controller.php";
include_once __DIR__."/class.signs_controller.php";
include_once __DIR__."/class.pagination_controller.php";
include_once __DIR__."/class.filter_controller.php";
include_once __DIR__."/class.academia_controller.php";
include_once __DIR__."/class.clicksign_controller.php";


define("ACADEMIA_DOMAIN", "https://api-lw15.learnworlds.com");
define("ACADEMIA_ACCESS_TOKEN", "TZXkqDX9nxrivB6pNFf3RJNIjHyncLcL3ch1IPXA"); //valid until 12/07/2023
define("ACADEMIA_CLIENT_ID", "62cd81001c440f668b022ed2");
define("ACADEMIA_DEFAULT_USER_PASS", "savecash@2022");

class Router{
    public function get_candidates($request){
        $candidateController = new CandidateController();
        $pagination_controller = new Pagination();

        $count = $candidateController->count();
        $pagination = $pagination_controller->get_pagination_config($request["offset"], $request["limit"], $count);
        $candidates = $candidateController->get_candidates($pagination);

        return [
            "status" => "success",
            "count" => $count,
            "next" => $pagination["next_url"],
            "previous" => $pagination["previous_url"],
            "data" => $candidates
        ];
    }

    public function get_candidate($request){
        $candidateController = new CandidateController();

        $phone = $request["phone"];
        $candidate = $candidateController->get_candidate_by_phone($phone);
        
        return [
            "status" => ($candidate == null) ? "not-found" : "success",
            "data" => $candidate
        ];
    }

    public function get_candidates_signs($request){
        $sign_controller = new SignsController();
        $pagination_controller = new Pagination();
        $filter_controller = new FilterController([
            ["field" => "sign_name", "value" => $request["name"]],
            ["field" => "sign_cpf", "value" => $request["cpf"]],
            ["field" => "sign_email", "value" => $request["email"]],
            ["field" => "sign_telefone", "value" => $request["phone"]],
            ["field" => "sign_recrutador", "value" => $request["recruiter"]],
            ["field" => "sign_contrato", "value" => $request["agreement"]],
            ["field" => "sign_assinatura", "value" => $request["sign"]],
            ["field" => "sign_token", "value" => $request["token"]],
        ]);

        $count = $sign_controller->count();
        $pagination = $pagination_controller->get_pagination_config($request["offset"], $request["limit"], $count);
        $candidates_signs = $sign_controller->get_candidates_signs($pagination, $filter_controller);

        return [
            "status" => "success",
            "count" => $count,
            "next" => $pagination["next_url"],
            "previous" => $pagination["previous_url"],
            "data" => $candidates_signs
        ];
    }

    public function get_candidate_sign($request){
        $sign_controller = new SignsController();

        $id = $request["id"];
        $candidate_sign = $sign_controller->get_candidate_sign($id);
        
        return [
            "status" => ($candidate_sign == null) ? "not-found" : "success",
            "data" => $candidate_sign
        ];
    }

    public function get_team($request){
        $candidate_controller = new CandidateController();

        $id = $request["id"];
        $associative = $request["associative"] ?? true;
        $minimal = $request["minimal"] ?? true;

        return [
            "status" => "success",
            "data" => $candidate_controller->get_team($id, $associative, $minimal)
        ];   
    }

    public function create_academia_user($request){
        $candidate_controller = new CandidateController();
        $academia_controller = new AcademiaController(
            ACADEMIA_DOMAIN,
            ACADEMIA_CLIENT_ID,
            ACADEMIA_ACCESS_TOKEN
        );
        
        $id = $request["id"];
        $candidate = $candidate_controller->get_candidate_by_id($id);

        $result = $academia_controller->create_user($candidate, ACADEMIA_DEFAULT_USER_PASS);

        return [
            "status" => ($result) ? "success" : "failed",
            "data" => $result
        ];
    }

    public function on_certificate_awarded($request){
        //procurar no corpo da requisição o json e guarda-lo

        //procurar a qual candidato pertence o usuario

        //procurar post baseado no email

        //se encontrado adicionar informações do certificado no candidato
        //pegar id dos certificados aqui
        //https://api.learnworlds.com/user/:user_id/profile
        //pegar
    }
    
    public function on_course_completed($request){
        //procurar no corpo da requisição o json e guarda-lo

        //procurar a qual candidato pertence o usuario

        //procurar post baseado no email

        //se encontrado adicionar informações do certificado no candidato
        //pegar id e nome dos cursos aqui
        //https://api.learnworlds.com/user/:user_id/profile
        //buscar mais informação do curso aqui
        ////procurar no corpo da requisição o json e guarda-lo

        //procurar a qual candidato pertence o usuario

        //procurar post baseado no email

        //se encontrado adicionar informações do certificado no candidato
        //pegar id dos certificados aqui
        //https://api.learnworlds.com/user/:user_id/profile
    }

    public function candidate_action($request){
        $data = json_decode($request->get_body(), true);
        $action = (string)$data["trigger"];

        $actions = [
            "distract-candidate" => function($data){
                $candidate_controller = new CandidateController();
                return $candidate_controller->distract_candidate(intval($data["from"]), intval($data["to"]));
            },
            "replace-recruiter" => function($data){
                $candidate_controller = new CandidateController();
                return $candidate_controller->replace_recruiter(intval($data["from"]), intval($data["to"]));
            },
            "distract-team" => function($data){
                $candidate_controller = new CandidateController();
                return $candidate_controller->distract_team(intval($data["id"]));
            },
            "send-distract" => function($data){
                $clicksign = new ClickSign();
                return $clicksign->schedule_send_distract(intval($data["id"]));
            }
        ];
        
        if (is_null($actions[$action])){
            return ["status" => "failed", "error" => "invalid trigger", "trigger_list" => array_keys($actions)];
        }

        try {
            $data = $actions[$action]($data);
            return ["status" => "success", "date" => date_timestamp_get(date_create()), "data" => $data];
        } catch (Exception $e) {
            return ["status" => "failed", "data" => [], "error" => $e];
        }
    }
}