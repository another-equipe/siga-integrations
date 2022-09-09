<?php

include_once __DIR__ . "/../../constants.php";
include_once __DIR__ . "/class.historic.php";
include_once __DIR__ . "/class.candidate_controller.php";
include_once __DIR__ . "/class.signs_controller.php";
include_once __DIR__ . "/class.pagination_controller.php";
include_once __DIR__ . "/class.filter_controller.php";
include_once __DIR__ . "/class.academia_controller.php";
include_once __DIR__ . "/class.clicksign_controller.php";
include_once __DIR__ . "/class.bitrix_syncer.php";
include_once __DIR__ . "/class.syncer_tree_strategy.php";
include_once __DIR__ . "/class.syncer_simple_tree_strategy.php";

class Router
{
    public function get_candidates($request)
    {
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

    public function get_candidate($request)
    {
        $candidateController = new CandidateController();

        $phone = $request["phone"];
        $candidate = $candidateController->get_candidate_by_phone($phone);

        return [
            "status" => ($candidate == null) ? "not-found" : "success",
            "data" => $candidate
        ];
    }

    public function get_candidates_signs($request)
    {
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

    public function get_candidate_sign($request)
    {
        $sign_controller = new SignsController();

        $id = $request["id"];
        $candidate_sign = $sign_controller->get_candidate_sign($id);

        return [
            "status" => ($candidate_sign == null) ? "not-found" : "success",
            "data" => $candidate_sign
        ];
    }

    public function get_team($request)
    {
        $candidate_controller = new CandidateController();

        $id = $request["id"];
        $associative = $request["associative"] ?? true;
        $minimal = $request["minimal"] ?? true;
        $count = $request["count"] ?? false;

        return [
            "status" => "success",
            "data" => $candidate_controller->get_team($id, $associative, $minimal, $count)
        ];
    }

    public function create_academia_user($request)
    {
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

    public function on_certificate_awarded($request)
    {
        //procurar no corpo da requisição o json e guarda-lo

        //procurar a qual candidato pertence o usuario

        //procurar post baseado no email

        //se encontrado adicionar informações do certificado no candidato
        //pegar id dos certificados aqui
        //https://api.learnworlds.com/user/:user_id/profile
        //pegar
    }

    public function on_course_completed($request)
    {
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

    public function candidate_action($request)
    {
        $data = json_decode($request->get_body(), true);
        $action = (string)$data["trigger"];

        $actions = [
            "distract-candidate" => function ($data) {
                $candidate_controller = new CandidateController();

                $result_siga = $candidate_controller->distract_candidate(intval($data["from"]), intval($data["to"]));

                return $result_siga;
            },
            "replace-recruiter" => function ($data) {
                $candidate_controller = new CandidateController();
                return $candidate_controller->replace_recruiter(intval($data["from"]), intval($data["to"]));
            },
            "distract-team" => function ($data) {
                $candidate_controller = new CandidateController();

                $result = $candidate_controller->distract_team(intval($data["id"]), function ($team) {
                    $bitrix = new Bitrix(true);

                    foreach ($team as $hierarquie) {
                        foreach ($hierarquie as $candidate) {
                            $bitrix_user = $bitrix->get_bitrix_user($candidate["candidate_email"]);

                            if (!is_null($bitrix_user)) {
                                $deals = $bitrix->get_deals_by_bitrix_id($bitrix_user["ID"]);
                                foreach ($deals as $deal) {
                                    $bitrix->delete_deal(intval($deal["ID"]));
                                }

                                $child_departments = $bitrix->get_child_departments($bitrix_user["UF_DEPARTMENT"][0]);
                                foreach ($child_departments as $department) {
                                    $bitrix->delete_department($department["ID"]);
                                }

                                $bitrix->deactivate_user(intval($bitrix_user["ID"]));
                                $bitrix->delete_department(intval($bitrix_user["UF_DEPARTMENT"][0]));
                            }
                        }
                    }
                });

                return $result;
            },
            "promote-candidate" => function ($data) {
                $candidate_controller = new CandidateController();
                return $candidate_controller->promote_candidate(
                    intval($data["id"]),
                    intval($data["new_recruiter_id"]),
                    $data["hierarquie"],
                    intval($data["to_assume"])
                );
            },
            "send-distract" => function ($data) {
                $clicksign = new ClickSign();
                $Historic = new Historic();

                $clicksign->schedule_send_distract(intval($data["id"]));

                $name = get_post(intval($data["id"]))->post_title;
                $Historic->new([
                    "title" => "[send distract scheduled] - " . $name,
                    "action" => "send distract scheduled",
                    "who_received" => $name
                ]);

                return intval($data["id"]);
            },
            "send-spc-contract" => function ($data) {
                $clicksign = new ClickSign();
                $Historic = new Historic();

                $clicksign->schedule_send_SPC_contract(intval($data["id"]));

                $name = get_post(intval($data["id"]))->post_title;
                $Historic->new([
                    "title" => "[send SCP contract scheduled] - " . $name,
                    "action" => "send SCP contract scheduled",
                    "who_received" => $name
                ]);

                return intval($data["id"]);
            },
            "send-attachment-contract" => function ($data) {
                $clicksign = new ClickSign();
                $Historic = new Historic();

                $clicksign->schedule_send_attachment_contract(intval($data["id"]));

                $name = get_post(intval($data["id"]))->post_title;
                $Historic->new([
                    "title" => "[send attachment contract scheduled] - " . $name,
                    "action" => "send attachment contract scheduled",
                    "who_received" => $name
                ]);

                return ["id" => intval($data["id"])];
            }
        ];

        if (is_null($actions[$action])) {
            return ["status" => "failed", "error" => "invalid trigger", "trigger_list" => array_keys($actions)];
        }

        try {
            $data = $actions[$action]($data);
            return ["status" => "success", "date" => date_timestamp_get(date_create()), "data" => $data];
        } catch (\Exception $f) {
            return ["status" => "failed", "data" => [], "error" => $f->getMessage()];
        } catch (\Throwable $th) {
            return ["status" => "internal-server-error", "data" => [], "error" => (string)$th];
        }
    }

    public function bitrix_action($request)
    {
        $data = json_decode($request->get_body(), true);
        $action = (string)$data["trigger"];

        $actions = [
            "sync-org-structure" => function ($data) {
                
                if ($data["sync_strategy"] == "complete") {

                    $sync_strategy = new syncerTreeStrategy();

                    return $sync_strategy->sync(
                        intval($data["id"]),
                        $data["id_type"] ?? "siga",
                        [
                            "debug" => true,
                            "create_card" => true,
                            "root_department" => intval($data["root_department"] ?? DEFAULT_ROOT_DEPARTMENT) 
                        ]
                    );

                } else if ($data["sync_strategy"] == "simple"){

                    $sync_strategy = new syncerSimpleTreeStrategy();

                    return $sync_strategy->sync(
                        intval($data["id"]),
                        $data["id_type"] ?? "siga",
                        [
                            "debug" => true,
                            "root_department" => intval($data["root_department"] ?? DEFAULT_ROOT_DEPARTMENT) 
                        ]
                    );
                }
            },
            "register_bitrix_user" => function ($data) {
                $bitrix = new Bitrix(true);

                if ($data["id"]) {
                    $candidate = $bitrix->get_user_data(intval($data["id"]));

                    $result = $bitrix->register_bitrix_user($candidate["name"], $candidate["email"], $candidate["role"], $candidate["department"]["ID"]);
                } else {
                    $result = $bitrix->register_bitrix_user($data["name"], $data["email"], $data["role"], $data["department"]);
                }

                return $result;
            },

            "fetch-bitrix" => function($data) {
                $bitrix = new Bitrix(true);

                return $bitrix->fetch_bitrix($data["url"] ?? "");
            },


            "update_user_bitrix" => function($data) {
                $bitrix = new Bitrix(true);

                $user = $bitrix->get_bitrix_by_user_id(intval($data["id"]));

                if($user){

                    $result = $bitrix->update_user_on_bitrix($user, $data["email"]);

                } else {
                    $result = "ID DO SIGA INVALIDO, meu gafanhoto. Insira um ID valido para você ir para um another nivel";
                }

                return $result;
            }
        ];

        if (is_null($actions[$action])) {
            return ["status" => "failed", "error" => "invalid trigger", "trigger_list" => array_keys($actions)];
        }

        try {
            $data = $actions[$action]($data);
            return ["status" => "success", "date" => date_timestamp_get(date_create()), "data" => $data];
        } catch (\Exception $f) {
            return ["status" => "failed", "data" => [], "error" => $f->getMessage()];
        } catch (\Throwable $th) {
            return ["status" => "failed", "data" => [], "error" => (string)$th];
        }
    }
}