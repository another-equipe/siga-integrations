<?php

include_once __DIR__ . "/../../constants.php";
include_once __DIR__ . "./../../utils/sanitize_metas.php";
include_once __DIR__ . "/class.clicksign_controller.php";

class CandidateController
{
    public function get_candidates($pagination = ["offset" => 0, "limit" => 100]): array
    {
        global $wpdb;

        $sql = "
        SELECT 
            ID, 
            post_title 
        FROM 
            wp_posts 
        WHERE ID IN (
            SELECT 
                DISTINCT post_id
            FROM 
                wp_postmeta 
            WHERE 
                meta_key = 'c_status'
                AND meta_value = 'contratado' 
                OR meta_value = 'distratado'
            ) 
        LIMIT 
            %d, %d;
        ";
        $query = $wpdb->prepare($sql, $pagination["offset"], $pagination["limit"]);
        $candidates = $wpdb->get_results($query);
        $candidates_posts = [];

        foreach ($candidates as $candidate) {
            $post = get_post_meta($candidate->ID);
            $post = sanitize_metas($post);

            $candidate_post = [
                "id"                    => $candidate->ID,
                "candidate_nome"        => $candidate->post_title,
                "candidate_email"       => $post["c_email"],
                "candidate_vaga"        => $post["c_vaga"],
                "candidate_telefone"    => $post["c_telefone"],
                "candidate_cpf"         => $post["c_cpf"],
                "candidate_cnpj"        => $post["c_cnpj"],
                "candidate_razao"       => $post["c_razao"],
                "candidate_status"      => $post["c_status"],
                "recruiter_nome"        => $post["c_recrutador_nome"],
                "recruiter_email"       => $post["c_recrutador"],
                "recruiter_telefone"    => $post["c_recrutador_telefone"],
            ];

            array_push($candidates_posts, $candidate_post);
        }

        return $candidates_posts;
    }

    public function get_candidate_by_phone(string $phone): array
    {
        global $wpdb;

        $sql = "
        SELECT 
            ID, 
            post_title 
        FROM 
            wp_posts 
        WHERE ID IN (
            SELECT 
                post_id
            FROM 
                wp_postmeta 
            WHERE 
                meta_key = 'c_telefone'
                AND meta_value LIKE REGEXP_REPLACE('%s', '[^0-9]+', '')
            )
        ";
        $query = $wpdb->prepare($sql, $phone);
        $candidate = $wpdb->get_results($query)[0];

        if (is_null($candidate)) return null;

        $post = get_post_meta($candidate->ID);
        $post = sanitize_metas($post);

        $candidate_post = [
            "id"                    => $candidate->ID,
            "candidate_nome"        => $candidate->post_title,
            "candidate_email"       => $post["c_email"],
            "candidate_vaga"        => $post["c_vaga"],
            "candidate_telefone"    => $post["c_telefone"],
            "candidate_cpf"         => $post["c_cpf"],
            "candidate_cnpj"        => $post["c_cnpj"],
            "candidate_razao"       => $post["c_razao"],
            "candidate_status"      => $post["c_status"],
            "recruiter_nome"        => $post["c_recrutador_nome"],
            "recruiter_email"       => $post["c_recrutador"],
            "recruiter_telefone"    => $post["c_recrutador_telefone"]
        ];

        return $candidate_post;
    }

    public function get_candidate_by_id($id)
    {
        global $wpdb;

        $sql = "SELECT ID, post_title FROM wp_posts WHERE ID = %d";
        $query = $wpdb->prepare($sql, $id);
        $candidate = $wpdb->get_results($query)[0];

        if (is_null($candidate)) return null;

        $post = get_post_meta($candidate->ID);
        $post = sanitize_metas($post);

        $candidate_post = ($candidate == null) ? null : [
            "id"                    => $candidate->ID,
            "candidate_nome"        => $candidate->post_title,
            "candidate_email"       => $post["c_email"],
            "candidate_vaga"        => $post["c_vaga"],
            "candidate_telefone"    => $post["c_telefone"],
            "candidate_cpf"         => $post["c_cpf"],
            "candidate_cnpj"        => $post["c_cnpj"],
            "candidate_razao"       => $post["c_razao"],
            "candidate_status"      => $post["c_status"],
            "recruiter_nome"        => $post["c_recrutador_nome"],
            "recruiter_email"       => $post["c_recrutador"],
            "recruiter_telefone"    => $post["c_recrutador_telefone"],
        ];

        return $candidate_post;
    }

    public function count(): int
    {
        global $wpdb;

        $sql = "
        SELECT 
            COUNT(ID) AS count
        FROM 
            wp_posts
        WHERE
            post_type = '%s'
            AND ID IN (
                SELECT 
                    DISTINCT post_id
                FROM 
                    wp_postmeta 
                WHERE 
                    meta_key = 'c_status' 
                    AND meta_value = 'contratado' 
                    OR meta_value = 'distratado'        
            )
        ";
        $query = $wpdb->prepare($sql, CANDIDATES_SLUG);
        $count = $wpdb->get_results($query)[0]->count;

        return intval($count);
    }

    public function distract_team(int $node, ?callable $on_get_team): array
    {
        $team = $this->get_team($node);

        if (is_callable($on_get_team)) $on_get_team($team);

        $team = array_slice($team, 1);

        foreach ($team as $hierarquie) {
            foreach ($hierarquie as $member) {
                $clicksign_controller = new ClickSign();

                $this->delete_user_by_id($this->get_user_by_email($member["candidate_email"]));
                update_post_meta($member["id"], "c_status", "distratado");
                $clicksign_controller->schedule_send_distract($member["id"]);


                $member_name = get_post(intval($member["id"]))->post_title;
            }
        }

        return ["distracted_team" => $team];
    }

    public function distract_candidate(int $to_distract, int $to_assume): array
    {
        $clicksign_controller = new ClickSign();

        $candidate_to_distract = $this->get_candidate_by_id($to_distract);
        $candidate_to_assume = $this->get_candidate_by_id($to_assume);

        if ($candidate_to_distract["candidate_vaga"] != $candidate_to_assume["candidate_vaga"]) throw new Exception("candidates must be the same hierarquie");

        $this->delete_user_by_id($this->get_user_by_email($candidate_to_distract["candidate_email"]));
        update_post_meta($to_distract, "c_status", "distratado");
        $data = $this->replace_recruiter($to_distract, $to_assume);
        $clicksign_controller->schedule_send_distract($to_distract);

        $post_arr = [
            "post_title" => $candidate_to_distract["candidate_nome"],
            "post_type" => WEBHOOKS_SLUG,
            'post_status' => 'publish',
            "meta_input" => [
                "sc_trigger" => "distract",
                "sc_id" => $to_distract,
                "sc_data" => json_encode([
                    "id" => $to_distract,
                    "candidate" => $candidate_to_distract["candidate_email"],
                    "to_assume" => $candidate_to_assume["candidate_email"], 
                    "name" => $candidate_to_distract["candidate_nome"],
                    "function" => $candidate_to_distract["candidate_vaga"],
                ]),
                "sc_event_status" => "waiting",
                "sc_response_return" => ""
            ]
        ];
        
        wp_insert_post($post_arr);

        return [
            "distracted" => $candidate_to_distract,
            "assumed" => $candidate_to_assume,
            "old_team" => $data["old_team"],
            "new_team" => $data["new_team"]
        ];
    }

    public function promote_candidate(int $id, int $new_recruiter_id, string $hierarquie, ?int $to_assume)
    {
        $clicksign_controller = new ClickSign();

        $allowed_hierarquies = ["diretor", "lider", "gerente", "supervisor", "consultor"];

        $candidate = $this->get_candidate_by_id($id);
        $candidate_superior = $this->get_candidate_by_id($new_recruiter_id);
        $candidate_to_assume_team = $this->get_candidate_by_id($to_assume);

        if (!in_array($hierarquie, $allowed_hierarquies, true)) throw new Exception("Invalid hierarquie, try 'diretor', 'lider', 'gerente' or 'supervisor'");
        // if (is_null($candidate_superior["candidate_vaga"])) throw new Exception("Not able to promote without a new recruiter");
        // if ($hierarquie == $candidate["candidate_vaga"]) throw new Exception("Not able to promote to a equal hierarquie");
        if (is_null($candidate)) throw new Exception("Candidate with id $id not found");
        // if ($candidate["candidate_vaga"] != "consultor" && is_null($candidate_to_assume_team)) throw new Exception("Candidate with id $id that will assume team not found");

        $this->replace_recruiter($id, $to_assume, false);

        update_post_meta($id, "c_vaga", $hierarquie);

        update_post_meta($candidate["id"], "c_recrutador", $candidate_superior["candidate_email"]);
        update_post_meta($candidate["id"], "c_recrutador_nome", $candidate_superior["candidate_nome"]);
        update_post_meta($candidate["id"], "c_recrutador_telefone", $candidate_superior["candidate_telefone"]);

        $clicksign_controller->schedule_send_distract($id);
        $clicksign_controller->schedule_send_SPC_contract($id);
        $clicksign_controller->schedule_send_attachment_contract($id);

        $this->update_user_role(
            $this->get_user_by_email($candidate["candidate_email"]),
            $hierarquie
        );

        $post_arr = [
            "post_title" => $candidate["candidate_nome"],
            "post_type" => WEBHOOKS_SLUG,
            'post_status'   => 'publish',
            "meta_input" => [
                "sc_trigger" => "promote",
                "sc_id" => $id,
                "sc_data" => json_encode([
                    "id" => $id,
                    "old_recruiter" => $candidate["recruiter_email"],
                    "new_recruiter" => $candidate_superior["candidate_email"],
                    "candidate" => $candidate["candidate_email"],
                    "candidate_to_assume" => $candidate_to_assume_team["candidate_email"],
                    "name" => $candidate["candidate_nome"],
                    "function" => $candidate["candidate_vaga"],
                    "new_function" => $hierarquie,
                ]),
                "sc_event_status" => "waiting",
                "sc_response_return" => ""
            ]
        ];
        
        $post_id = wp_insert_post($post_arr);

        return [
            "old_hierarquie" => $candidate["candidate_vaga"],
            "hierarquie" => $hierarquie,
            "assumed" => $to_assume,
        ];
    }

    public function update_user_role(int $user_id, string $role)
    {
        global $wpdb;

        $sql = "UPDATE wp_usermeta SET meta_value = '%s' WHERE meta_key = 'u_funcao' AND user_id = %d";
        $query = $wpdb->prepare($sql, $role, $user_id);
        $wpdb->get_results($query);
    }

    public function replace_recruiter(int $from, int $to): array
    {
        $candidate_to_be_replaced = $this->get_candidate_by_id($from);
        $candidate = $this->get_candidate_by_id($to);
        $team = $this->get_team($from);
        $team_members = array_slice($team, 1);

        $first_hierarquie = true;
        foreach ($team_members as $hierarquie) {
            foreach ($hierarquie as $member) {
                if ($first_hierarquie) {
                    update_post_meta($member["id"], "c_recrutador", $candidate["candidate_email"]);
                    update_post_meta($member["id"], "c_recrutador_nome", $candidate["candidate_nome"]);
                    update_post_meta($member["id"], "c_recrutador_telefone", $candidate["candidate_telefone"]);
                }

                update_post_meta($member["id"], "e_" . $candidate["candidate_vaga"] . "_nome", $candidate["candidate_nome"]);
                update_post_meta($member["id"], "e_" . $candidate["candidate_vaga"] . "_telefone", $candidate["candidate_telefone"]);
            }
            $first_hierarquie = false;
        }

        return [
            "old_moved" => $team,
            "new_team" => $this->get_team($to, true)
        ];
    }

    public function get_team(int $node, bool $associative = false, ?bool $minimal = true, ?bool $count = false): array
    {
        global $wpdb;

        $sql_get_first_node = "SELECT ID FROM wp_posts WHERE ID = %d";
        $query_get_first_node = $wpdb->prepare($sql_get_first_node, $node);
        $first_node_id = $wpdb->get_results($query_get_first_node)[0]->ID;

        if (is_null($first_node_id)) return [];

        $first_node = $minimal ? [
            "id" => $first_node_id,
            "candidate_email" => get_post_meta($first_node_id, "c_email", true),
            "recruiter_email" => get_post_meta($first_node_id, "c_recrutador", true),
            "candidate_vaga" => get_post_meta($first_node_id, "c_vaga", true)
        ]
        :
        $this->get_candidate_by_id($first_node_id);

        $tree = [[$first_node]];

        while (true) {
            $sublevel = [];
            $current_tree_hierarquie = end($tree);

            foreach ($current_tree_hierarquie as $candidate) {
                $sql_get_nodes = "SELECT ID FROM wp_posts WHERE ID IN (SELECT DISTINCT post_id FROM wp_postmeta WHERE meta_key = 'c_recrutador' AND meta_value = '%s') AND ID IN (SELECT DISTINCT post_id FROM wp_postmeta WHERE meta_key = 'c_status' AND meta_value != 'distratado')";
                $query_get_nodes = $wpdb->prepare($sql_get_nodes, $candidate["candidate_email"]);
                $id_list = array_column($wpdb->get_results($query_get_nodes), "ID");

                if (sizeof($id_list) == 0) continue;

                $sublevel = [
                    ...$sublevel,
                    ...array_map(function ($id) {
                        global $minimal;
                        global $count;

                        if ($count) {
                            return [
                                "id" => $id,
                                "candidate_email" => get_post_meta($id, "c_email", true),
                                "candidate_vaga" => get_post_meta($id, "c_vaga", true)
                            ];
                        }

                        if ($minimal) {
                            return [
                                "id" => $id,
                                "candidate_email" => get_post_meta($id, "c_email", true),
                                "recruiter_email" => get_post_meta($id, "c_recrutador", true),
                                "recruiter_nome" => get_post_meta($id, "c_recrutador_nome", true),
                                "candidate_vaga" => get_post_meta($id, "c_vaga", true)
                            ];
                        }

                        return $this->get_candidate_by_id(intval($id));
                    }, $id_list)
                ];
            }

            if (sizeof($sublevel) == 0) break;

            array_push($tree, $sublevel);
        }

        $counted_tree = [];
        $associative_tree = [];

        if ($count) {
            if ($associative) {
                foreach ($tree as $tree_hierarquie) {
                    $associative_tree[$tree_hierarquie[0]["candidate_vaga"]] = sizeof($tree_hierarquie);
                }

                return $associative_tree;
            } else {
                foreach ($tree as $tree_hierarquie) {
                    array_push($counted_tree, sizeof($tree_hierarquie));
                }

                return $counted_tree;
            }
        }

        if ($associative) {
            foreach ($tree as $tree_hierarquie) {
                $associative_tree[$tree_hierarquie[0]["candidate_vaga"]] = $tree_hierarquie;
            }

            return $associative_tree;
        }

        return $tree;
    }

    private function get_imediate_superior($candidate)
    {
        global $wpdb;

        if (CANDIDATE_HIERARQUIES[$candidate["candidate_vaga"]] == CANDIDATE_HIERARQUIES["diretor"]) return null;

        $sql = "SELECT post_id FROM wp_postmeta WHERE meta_key = 'c_email' AND meta_value = '%s' LIMIT 1";
        $query = $wpdb->prepare($sql, $candidate["recruiter_email"]);
        $superior_id = $wpdb->get_results($query)[0]->post_id;
        $superior = $this->get_candidate_by_id($superior_id);

        return $superior;
    }

    private function get_user_by_email(?string $email): ?int
    {
        global $wpdb;

        $sql = "SELECT ID FROM wp_users WHERE user_email = '%s'";
        $query = $wpdb->prepare($sql, $email);
        $user_id = $wpdb->get_results($query)[0]->ID;

        return $user_id;
    }

    private function delete_user_by_id(?int $id): bool
    {
        global $wpdb;

        $sql_delete_users_metas = "DELETE FROM wp_usermeta WHERE user_id = %d";

        $sql_delete_user = "DELETE FROM wp_users WHERE ID = %d;";

        $query_delete_users_metas = $wpdb->prepare($sql_delete_users_metas, $id);
        $query_delete_users = $wpdb->prepare($sql_delete_user, $id);

        $query_result1 = $wpdb->get_results($query_delete_users_metas);
        $query_result2 = $wpdb->get_results($query_delete_users);

        return $query_result1 && $query_result2;
    }

    public function get_superiors_recursive($candidate)
    {
        $superiors = [
            "imediate_superior" => null,
            "diretor" => null,
            "lider" => null,
            "gerente" => null,
            "supervisor" => null
        ];

        if (CANDIDATE_HIERARQUIES[$candidate["candidate_vaga"]] == CANDIDATE_HIERARQUIES["diretor"]) return $superiors;

        $superiors_cascade = [$candidate];
        $superiors["imediate_superior"] = array_flip(CANDIDATE_HIERARQUIES)[CANDIDATE_HIERARQUIES[$candidate["candidate_vaga"]] - 1];

        while (end($superiors_cascade)["candidate_vaga"] != "diretor") {
            $last_item = end($superiors_cascade);

            if (is_null($last_item)) break;

            $superior = $this->get_imediate_superior($last_item);
            array_push($superiors_cascade, $superior);
            $superiors[array_flip(CANDIDATE_HIERARQUIES)[CANDIDATE_HIERARQUIES[$last_item["candidate_vaga"]] - 1]] = $superior;
        }

        return $superiors;
    }
}
