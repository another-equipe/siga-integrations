<?php

define("ROOT_DEPARTMENT", 23115);

class Bitrix {
    private $last_candidate_sincronized;
    protected $write_logs;
    public $root_department;

    public function __constructor(bool $debug = false, ?int $root_department){
        $this->last_candidate_sincronized = null;
        $this->write_logs = $debug;
        $this->root_department = $root_department ?? ROOT_DEPARTMENT;
    }

    public function sync_candidate(int $id, string $id_type = "siga", ?bool $create_card = true): array{

        $this->last_candidate_sincronized = ["id" => $id, "id_type" => $id_type];

        $candidate_data = $this->get_user_data($id, $id_type);

        if (boolval($candidate_data["bitrix_user"]) && boolval($candidate_data["department"])) {
            if ($this->write_logs) $this->create_log("[sync_bitrix] user with id ".$id." already synced");

            $candidate_data["updates"] = $this->update_candidate(
                intval($candidate_data["siga_id"]),
                $candidate_data["accepted_invitation"],
                intval($candidate_data["bitrix_user"]["ID"])
            );
        }

        if (sizeof($candidate_data) == 0){
            if ($this->write_logs) $this->create_log("[sync_bitrix] user data not found", "error");

            throw new Exception("user data not found");
        }
        
        if (is_null($candidate_data["bitrix_user"])){
            $new_id = $this->register_bitrix_user(
                $candidate_data["name"],
                $candidate_data["email"],
                $candidate_data["role"],
                $candidate_data["department"] ?? $this->root_department
            );

            $candidate_data["bitrix_user"] = ["ID" => $new_id["result"]];
        }

        if (is_null($candidate_data["recruiter_department"]) && $candidate_data["role"] != "diretor"){
            $sync_recruiter_data = $this->sync_candidate($candidate_data["recruiter_id"]);
            $candidate_data["recruiter_department"] = $sync_recruiter_data["department"];
        }

        if (is_null($candidate_data["department"])){
            $this->register_user_department(
                $candidate_data["role"],
                $candidate_data["email"],
                $candidate_data["role"] == "diretor" ? $this->root_department : $candidate_data["recruiter_department"]["ID"],
            );

            $candidate_data["department"] = $this->get_user_department($candidate_data["email"]);

            $this->set_head_of_department(
                intval($candidate_data["bitrix_user"]["ID"]),
                $candidate_data["department"]["ID"]
            );
        }
        

        if (boolval($candidate_data["department"]) && boolval($candidate_data["bitrix_user"])){
            $this->update_user_department(
                intval($candidate_data["bitrix_user"]["ID"]),
                $candidate_data["department"]["ID"], 
                $candidate_data["role"]
            );
        }

        if ($create_card){
            $onboard_cards_id = $this->get_deals_by_bitrix_id(intval($candidate_data["bitrix_user"]["ID"]));

            if (sizeof($onboard_cards_id) == 0){
                $this->create_deal(
                    $candidate_data["name"],
                    intval($candidate_data["bitrix_user"]["ID"])
                );
            }
        }

        $candidate_data["updates"] = $this->update_candidate(
            intval($candidate_data["siga_id"]),
            $candidate_data["accepted_invitation"],
            intval($candidate_data["bitrix_user"]["ID"])
        );

        return $candidate_data;
    }

    public function create_log(string $content, string $type = "info"): bool{
        $date = date("e j/m - H:i");
        $log = "[$date]\t$type - $content\n";

        return boolval(file_put_contents(__DIR__.'./../../logs/bitrix.log', $log, FILE_APPEND));
    }

    public function get_user_data(int $id, string $id_type = "siga"): array{
        $candidate_data;

        if ($id_type == "siga") {
            $candidate_data["user_id"] = $this->get_user_by_siga_id($id);
            $candidate_data["siga_id"] = $id;
        }
        
        if ($id_type == "bitrix"){
            $candidate_data["user_id"] = $this->get_user_by_bitrix_id($id);
            $candidate_data["siga_id"] = $this->get_siga_id_by_user_id($candidate_data["user_id"]);
        }

        if ($id_type == "user"){
            $candidate_data["user_id"] = $id;
            $candidate_data["siga_id"] = $this->get_siga_id_by_user_id($candidate_data["user_id"]);
        }

        try {
            $candidate_data["name"] = get_post($candidate_data["siga_id"])->post_title;
            $candidate_data["email"] = get_post_meta($candidate_data["siga_id"], "c_email", true);
            $candidate_data["bitrix_user"] = $this->get_bitrix_user($candidate_data["email"]);
            $candidate_data["role"] = get_user_meta($candidate_data["user_id"], "u_funcao", true);
            $candidate_data["department"] = $this->get_user_department($candidate_data["email"]);

            $candidate_data["recruiter_email"] = get_post_meta($candidate_data["siga_id"], "c_recrutador", true);
            $candidate_data["recruiter_department"] = $this->get_user_department($candidate_data["recruiter_email"]);
            $candidate_data["recruiter_id"] = $this->get_recruiter_id($candidate_data["recruiter_email"]);
            $candidate_data["recruiter_role"] = get_post_meta($candidate_data["recruiter_id"], "c_vaga", true) ?? null;

            $candidate_data["accept_invitation"] = $candidate_data["bitrix_user"]["LAST_LOGIN"];
        } catch (\Throwable $e){
            if ($this->write_logs) $this->create_log("[get_user_data] ".(string)$e, "error");
        }

        if ($this->write_logs) $this->create_log("[get_user_data] data collected: ".json_encode($candidate_data));
        
        return $candidate_data;
    }

    public function get_user_by_siga_id(int $siga_id): int{
        global $wpdb;

        $email = get_post_meta($siga_id, "c_email", true);

        if ($this->write_logs) $this->create_log("[get_user_by_siga_id] email: ".$email);
        
        if (!$email){
            if ($this->write_logs) $this->create_log("[get_user_by_siga_id] siga_id ".$siga_id." not found", "error");

            throw new Exception("siga_id ".$siga_id." not found");
        }
        
        $sql = "SELECT ID FROM wp_users WHERE user_email = '%s'";
        $query = $wpdb->prepare($sql, $email);
        $result = $wpdb->get_results($query);
        
        if (sizeof($result) == 0){
            if ($this->write_logs) $this->create_log("[get_user_by_siga_id] user not found", "error");

            throw new Exception("user not found");
        }
        
        return $result[0]->ID;
    }

    public function get_user_by_bitrix_id(int $bitrix_id): int{
        global $wpdb;

        $sql = "SELECT ID FROM wp_users WHERE ID = (SELECT user_id FROM wp_usermeta WHERE meta_key = 'u_id_no_bitrix' AND meta_value = '%d')";
        $query = $wpdb->prepare($sql, $bitrix_id);
        $result = $wpdb->get_results($query);

        if (sizeof($result) == 0){
            if ($this->write_logs) $this->create_log("[get_user_by_bitrix_id] user not found with bitrix_id ".$bitrix_id, "error");

            throw new Exception("user not found");
        }

        if ($this->write_logs) $this->create_log("[get_user_by_bitrix_id] user with ID ".$result[0]->ID." found");

        return $result[0]->ID;
    }

    public function get_siga_id_by_user_id(int $user_id): int{
        global $wpdb;

        $user = get_user_by("ID", $user_id);

        if (!$user) {
            if ($this->write_logs) $this->create_log("[get_siga_id_by_user_id] user with id ".$user_id." not found", "error");

            throw new Exception("user with id ".$user_id." not found");
        }

        $sql = "SELECT ID FROM wp_posts WHERE ID = (SELECT user_id FROM wp_postmeta WHERE meta_key = 'c_email' AND meta_value = '%s')";
        $query = $wpdb->prepare($sql, $user->user_email);
        $result = $wpdb->get_results($query);

        if (sizeof($result) == 0){
            if ($this->write_logs) $this->create_log("[get_siga_id_by_user_id] user with id ".$user_id." not found", "error");

            throw new Exception("siga id for user ".$user_id." not found");
        }

        return $result[0]->ID;
    }

    public function get_bitrix_by_user_id(int $user_id): ?int{
        $id = intval(get_user_meta($user_id, "u_id_no_bitrix", true));

        if ($this->write_logs) $this->create_log("[get_bitrix_by_user_id] obtained bitrix id ".$id." by user id ".$user_id);

        return $id;
    }
  
    public function get_bitrix_user(string $email): ?array{
        $result = $this->fetch_bitrix("https://zillesg.bitrix24.com.br/rest/5/l773ryufgomyl7pd/user.get.json?filter[EMAIL]=$email");

        if ($this->write_logs) $this->create_log("[get_bitrix_user] user got: ".json_encode($result["result"][0]));

        return $result["result"][0];
    }
    
    public function get_user_department(string $email): ?array{
        $url = "https://zillesg.bitrix24.com.br/rest/5/wq5ux4eguljfyv3t/department.get.json?filter[NAME]=%%20$email";
        $result = $this->fetch_bitrix($url);

        if ($this->write_logs) $this->create_log("[get_bitrix_user] department got: ".json_encode($result["result"][0]));

        return $result["result"][0];
    }
    
    public function fetch_bitrix(string $url, ?array $args = []): ?array{

        if ($this->write_logs) $this->create_log("Fetching url ".$url);
        
        $result = json_decode(
            file_get_contents(
                $url,
                $args["use_include_path"] ?? false
            ),
            true
        );

        if ($this->write_logs) $this->create_log("Fetching result: ".json_encode($result));

        return $result;
    }

    public function post_bitrix(string $url, array $post_fields){
        $curl = curl_init();

        if ($this->write_logs) $this->create_log("[post_bitrix] POST Request to url ".$url);

        curl_setopt_array($curl, array(
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_POSTFIELDS => http_build_query($post_fields),
        ));

        $result = curl_exec($curl);

        if ($this->write_logs) $this->create_log("[post_bitrix] POST Request result: ".json_encode($result));

        return $result;
    }

    public function register_bitrix_user(string $name, string $email, string $role, ?int $department): ?array{
        $department = boolval($department) ? $department : $this->root_department;

        $last_name = trim(explode(' ', $name)[1] . ' ' . explode(' ', $name)[2] . ' ' . explode(' ', $name)[3] . ' ' . explode(' ', $name)[4]);
        $name = explode(' ', $name)[0];

        $url = "https://savecash.bitrix24.com.br/rest/5/qdolajv5wj7s15su/user.add.json?ACTIVE=true&NAME=$name&LAST_NAME=$last_name&EMAIL=$email&WORK_POSITION=$role&UF_DEPARTMENT=$department";
        $result = $this->fetch_bitrix($url);

        if ($this->write_logs) $this->create_log("[register_bitrix_user] New bitrix user created for ".$name." with id ".$result["ID"]);

        return $result;
    }

    public function register_user_department(string $role, string $email, int $parent_department_id): ?array {
        $result = $this->fetch_bitrix("https://savecash.bitrix24.com.br/rest/5/k68ksbh2fc28pzul/department.add.json?NAME=$role%20$email&PARENT=$parent_department_id");

        if ($this->write_logs) $this->create_log("[register_user_department] New department created for ".$email." with id ".$parent_department_id);
        if ($this->write_logs) $this->create_log("[register_user_department] Obtained: ".json_encode($result["result"][0]));

        return $result["result"][0];
    }

    public function update_user_department(int $bitrix_id, int $department_id, string $role): ?array{
        $result = $this->fetch_bitrix("https://savecash.bitrix24.com.br/rest/5/pt8f7m0ry4sidehx/user.update.json?ID=$bitrix_id&UF_DEPARTMENT=$department_id&WORK_POSITION=$role");

        if ($this->write_logs) $this->create_log("[update_user_department] Department updated for bitrix id ".$bitrix_id." and uf_department ".$department_id);
        if ($this->write_logs) $this->create_log("[update_user_department] Obtained: ".json_encode($result));

        return $result;
    }

    public function get_deals_by_bitrix_id(int $bitrix_id): ?array{
        $result = $this->fetch_bitrix("https://savecash.bitrix24.com.br/rest/5/wlc7nshosnel7yev/crm.deal.list.json?filter[ASSIGNED_BY_ID]=$bitrix_id");

        if ($this->write_logs) $this->create_log("[get_deals_by_bitrix_id] Obtained: ".json_encode($result));

        return $result["result"];
    }

    public function delete_card(int $deal_id): ?array{
        $result = $this->fetch_bitrix("https://savecash.bitrix24.com.br/rest/5/lz9hmsg0cta8fvuh/crm.deal.delete.json?ID=$deal_id");

        if ($this->write_logs) $this->create_log("[delete_card] Deal ".$deal_id." deleted");
        if ($this->write_logs) $this->create_log("[delete_card] Response: ".json_encode($result));

        return $result["result"][0];
    }

    public function assign_user_to_card(int $deal_id, int $bitrix_id){
        $result = $this->fetch_bitrix("https://savecash.bitrix24.com.br/rest/5/8efktob9v8sem6ma/crm.deal.update.json?ID=$deal_id&ASSIGNED_BY_ID=$bitrix_id");

        if ($this->write_logs) $this->create_log("[assign_user_to_card] Deal ".$deal_id." assigned to ".$bitrix_id);
        if ($this->write_logs) $this->create_log("[assign_user_to_card] Response: ".json_encode($result));

        return $result["result"][0];
    }

    public function create_deal(string $name, int $bitrix_id, ?array $args = []){
        $result = $this->post_bitrix(
            "https://savecash.bitrix24.com.br/rest/5/3afdy25dz6672b50/crm.deal.add.json",
            [
            'fields' => [
                "TITLE" => $name,
                "CATEGORY_ID" => $args["category_id"] ?? 17,
                "STAGE_ID" => $args["stage_id"] ?? "C17:NEW",
                "OPENED" => $args["opened"] ?? "Y",
                "ASSIGNED_BY_ID" => $bitrix_id,
                "TYPE_ID" => $args["type_id"] ?? "GOODS",
                "PROBABILITY" => $args["probability"] ?? "",
                "BEGINDATE" => date('Y-m-d H:i:s'),
                "CLOSEDATE" => date('Y-m-d H:i:s'),
                "OPPORTUNITY" => $args["opportunity"] ?? 0,
                "CURRENCY_ID" => $args["currency_id"] ?? "BRL",
                "UF_CRM_1650365006867" => $args["uf_crm_code"] ?? "https://academia.savecash.com.br",
                "COMMENTS" => $args["comments"] ?? "Comentário"
            ],
            'params' => [
                "REGISTER_SONET_EVENT" => $args["register_sonet_event"] ?? "Y"
            ]
        ]);

        if ($this->write_logs) $this->create_log("[create_deal] Deal created. Response:  ".json_encode($result));

        return $result;
    }

    public function update_candidate(int $siga_id, $accepted_invitation, int $bitrix_id): array{
        update_user_meta($siga_id, 'u_bitrix_sync', time());
        update_user_meta($siga_id, 'u_aceitou_o_convite', boolval($accepted_invitation) ? "sim" : "não");
        update_user_meta($siga_id, 'u_id_no_bitrix', $bitrix_id);
        
        if ($this->write_logs) $this->create_log("[delete_card] Candidate updated at ".time());

        return [
            "timestamp" => time(),
            "invitation" => boolval($accepted_invitation) ? "sim" : "não",
            "id" => $bitrix_id
        ];
    }

    public function get_recruiter_id(string $recruiter_email): ?int{
        global $wpdb;

        $sql = "SELECT ID FROM wp_posts WHERE ID = (SELECT post_id FROM wp_postmeta WHERE meta_key = 'c_email' AND meta_value = '%s')";
        $query = $wpdb->prepare($sql, $recruiter_email);
        $result = $wpdb->get_results($query);

        if (sizeof($result) == 0){
            if ($this->write_logs) $this->create_log("[get_recruiter_id] No recruiter found for email ".$recruiter_email);
            return null;
        }

        if ($this->write_logs) $this->create_log("[get_recruiter_id] Recruiter id: ".$result[0]->ID);

        return $result[0]->ID;
    }

    public function set_head_of_department(int $bitrix_id, int $department_id){
        $result = $this->fetch_bitrix("https://savecash.bitrix24.com.br/rest/5/qxur4b9a8wd4ydyq/department.update.json?UF_HEAD=$bitrix_id&ID=$department_id");

        if ($this->write_logs) $this->create_log("[set_head_of_department] department updated!");
        if ($this->write_logs) $this->create_log("[set_head_of_department] Response: ".json_encode($result));

        return $result["result"][0];
    }
}
