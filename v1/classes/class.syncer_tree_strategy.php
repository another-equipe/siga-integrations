<?php

include_once __DIR__ . "/class.bitrix.php";

class syncerTreeStrategy
{
    public function sync(
        int $id,
        string $id_type = "siga",
        ?array $options = [
            "debug" => false,
            "create_card" => true,
            "root_department" => 325
        ]
    ): array {
        ignore_user_abort(true);
        ini_set('max_execution_time', 350);
        ini_set("default_socket_timeout", 60000);

        $status = get_post_meta($id, "c_status", true);        

        if (!$status) {
            throw new Exception("ID do candidato não encontrado");
        }
        
        if ($status == "contratado") {
            return $this->sync_hired($id, $id_type, $options);
        } else if ($status == "distratado") {
            return $this->sync_absent($id, $id_type, $options);
        } else {
            throw new Exception("Para ser sincronizado com o bitrix o candidato deve ser um contratado ou distratado, um $status não pode ser sincronizado", "error");
        }
        
        return [];
    }

    private function sync_hired(int $id, string $id_type = "siga", ?array $options): array {
        $bitrix = new Bitrix($options["debug"]);

        $candidate_data = $bitrix->get_user_data($id, $id_type);
        
        if ($candidate_data["bitrix_user"]["ID"] && $candidate_data["department"]["ID"]) {
            $candidate_data["updates"] = $bitrix->update_candidate(
                intval($candidate_data["user_id"]),
                $candidate_data["accepted_invitation"],
                intval($candidate_data["bitrix_user"]["ID"])
            );
        }

        if (sizeof($candidate_data) == 0) {
            throw new Exception("user data not found");
        }

        if (is_null($candidate_data["recruiter_department"]["ID"]) && $candidate_data["role"] != "diretor") {
            $sync_recruiter_data = $this->sync(intval($candidate_data["recruiter_id"]));
            $candidate_data["recruiter_department"] = $sync_recruiter_data["department"];

            $bitrix->create_log("[sync_hired] candidato com id $id não tinha um superior com departamento criado, foi criado o departamento do superior com id ".$candidate_data["recruiter_department"]["ID"] ?? "[nenhum]", "error");
            
            flush();
        }

        if (is_null($candidate_data["department"]) && $candidate_data["recruiter_department"]["ID"]) {
			$department_id = $bitrix->register_user_department(
                $candidate_data["role"] . "%20" . $candidate_data["email"],
                $candidate_data["role"] == "diretor" ? $options["root_department"] : $candidate_data["recruiter_department"]["ID"]
            );

            if (is_null($department_id)) {
                $bitrix->create_log("[sync_hired] não foi possível criar o departamento para o candidato com id $id", "error");
            } else {
                $candidate_data["department"]["ID"] = $department_id;
            }
        } else {
            $bitrix->create_log("[sync_hired] um departamento para o candidato com id $id não pode ser criado, pois não é possível encontrar um departamento pai para ele. Verifique possíveis inconsistencias no cadastro", "error");
        }

        if (is_null($candidate_data["bitrix_user"])) {
            $try = 0;

            while ($try < 3) {
                $new_id = $bitrix->register_bitrix_user(
                    $candidate_data["name"],
                    $candidate_data["email"],
                    $candidate_data["role"],
                    $candidate_data["department"]["ID"] ?? $options["root_department"]
                );
    
                $candidate_data["bitrix_user"] = ["ID" => $new_id];

                if ($new_id){
                    break;
                }

                $try += 1;
            }
        }

        if ($candidate_data["department"]["ID"] && $candidate_data["bitrix_user"]["ID"]) {
            $set_head_response = $bitrix->set_head_of_department(
                intval($candidate_data["bitrix_user"]["ID"]),
                intval($candidate_data["department"]["ID"])
            );
        } else {
            $bitrix->create_log("[sync_hired] o candidato com id $id não pôde ser colocado como responsável pelo seu próprio departamento.", "error");
        }

        $candidate_data["department"]["updates"] = ["set_head_response" => $set_head_response];

        if ($candidate_data["department"]["ID"] && $candidate_data["bitrix_user"]["ID"]) {
            $bitrix->update_user_department(
                intval($candidate_data["bitrix_user"]["ID"]),
                intval($candidate_data["department"]["ID"]),
                $candidate_data["role"]
            );
        }

        if ($options["create_card"] && $candidate_data["bitrix_user"]["ID"]) {
            $onboard_cards = $bitrix->get_deals_by_name($candidate_data["name"]);

            if (sizeof($onboard_cards) == 0) {
                $bitrix->create_deal(
                    $candidate_data["name"],
                    intval($candidate_data["bitrix_user"]["ID"])
                );
            }
        }

        $candidate_data["updates"] = $bitrix->update_candidate(
            intval($candidate_data["user_id"]),
            $candidate_data["accepted_invitation"],
            intval($candidate_data["bitrix_user"]["ID"])
        );

        return $candidate_data;
    }

    private function sync_absent(int $id, string $id_type = "siga", ?array $options): array{
        global $wpdb;

        $bitrix = new Bitrix($options["debug"]);

        $candidate_data["email"] = get_post_meta($id, "c_email", true);
        $candidate_data["bitrix_user"] = $bitrix->get_bitrix_user($candidate_data["email"]);
        $candidate_data["department"] = $bitrix->get_user_department_by_email($candidate_data["email"]);

        if ($candidate_data["bitrix_user"]["ID"]) {
            $bitrix->deactivate_user(intval($candidate_data["bitrix_user"]["ID"]));
        }

/*      
        Pegar os filhos do departamento do distratado ✅
        Pegar o úsuario que tenha como departamento o primeiro departamento filho ✅
        Pegar os dados no SIGA desse úsuario✅
        Obter o departamento do recrutador desse úsuario ✅
        Atualizar o PARENT de todos os filhos para o departamento desse recrutador ✅
*/

        if (is_null($candidate_data["department"]["ID"])) {
            return $candidate_data;
        }

        $childs = $bitrix->get_child_departments($candidate_data["department"]["ID"]);

        $child_candidate_email = explode(" ", $childs[0]["NAME"])[1];

        $sql = "SELECT ID FROM wp_posts WHERE ID IN (SELECT post_id FROM wp_postmeta WHERE meta_key = 'c_email' AND meta_value = '%s') LIMIT 1";
        $query = $wpdb->prepare($sql, $child_candidate_email);
        $child_candidate_id = $wpdb->get_results($query)[0]->ID;
        $child_recruiter_email = get_post_meta($child_candidate_id, "c_recrutador", true);
        $child_recruiter_department = $bitrix->get_departments_by_email_in_name($child_recruiter_email)[0];
        
        $mh = curl_multi_init();
        $curl_list = [];

        foreach ($childs as $child_department) {
            $id = $child_department["ID"];
            $new_parent = $child_recruiter_department["ID"];
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, "https://savecash.bitrix24.com.br/rest/5/0m040o9i0y2m8g3b/department.update.json?ID=$id&PARENT=$new_parent");
            curl_setopt($ch, CURLOPT_HEADER, 0);

            curl_multi_add_handle($mh, $ch);

            array_push($curl_list, $ch);
        }

        $active = null;
        //execute the handles
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        
        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) != -1) {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        foreach ($curl_list as $curl) {
            curl_multi_remove_handle($mh, $curl);
        }

        curl_multi_close($mh);

        if ($candidate_data["department"]["ID"]) {
            $bitrix->delete_department(intval($candidate_data["department"]["ID"]));
        }

        return $candidate_data;
    }
}
