<?php

class Bitrix {
    protected $write_logs;

    public function __constructor(bool $debug = false){
        $this->write_logs = $debug;
    }
    
    /**
     * Gera uma nova linha no log
     * @param string $content conteúdo do Log
     * @param string $type tipo de log. Por padrão `info`
     * @param string $verify Por padrão, sempre é verificado se deve-se ou não fazer logs por meio de uma propriedade interna chamada `debug`.
     * Defina `false` para ignorar essa verificação
     * @return bool `true` quando bem sucessido. `false` quando falhar.
     */
    public function create_log(string $content, string $type = "info", bool $verify = false): bool {
        $path = WP_PLUGIN_DIR . "/siga-api/logs/" . (($type == "error") ? "bitrix_erros.log" : "bitrix.log");

        date_default_timezone_set('America/Sao_Paulo');
        $date = date("j/m - H:i");

        $log = "[$date]\t$type - $content\n";
        
        if ($verify){
            if ($this->write_logs){
                return boolval(file_put_contents($path, $log, FILE_APPEND));
            }
        } else {
            return boolval(file_put_contents($path, $log, FILE_APPEND));
        }

        return false;
    }

    /**
     * Obtem os dados essenciais de um saver
     * @param int $id
     * @param string $id_type Tipo de id. É possivel `siga`|`bitrix`|`user`. Por padrão é `siga`
     * @return array Informações básicas de um candidato no bitrix
    */
    public function get_user_data(int $id, string $id_type = "siga"): array{
        $candidate_data = [];

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
            $candidate_data["department"] = $this->get_user_department_by_email($candidate_data["email"]);
            
            $candidate_data["recruiter_email"] = get_post_meta($candidate_data["siga_id"], "c_recrutador", true);
            $candidate_data["recruiter_department"] = $this->get_user_department_by_email($candidate_data["recruiter_email"]);
            $candidate_data["recruiter_id"] = $this->get_recruiter_id($candidate_data["recruiter_email"]);
            $candidate_data["recruiter_role"] = get_post_meta($candidate_data["recruiter_id"], "c_vaga", true) ?? null;
            
            $candidate_data["accept_invitation"] = $candidate_data["bitrix_user"]["LAST_LOGIN"];
        } catch (\Throwable $e){
            $this->create_log("[get_user_data] ".(string)$e, "error", false);
        }

        return $candidate_data;
    }

    /**
     * Obtem o ID de um úsuario a partir do ID de post type.
     * Caso não seja encontrado, uma exçessão é lancada.
     * @param int $siga_id ID do post type
     * @return int ID de úsuario
    */
    public function get_user_by_siga_id(int $siga_id): int {
        global $wpdb;

        $email = get_post_meta($siga_id, "c_email", true);

        $this->create_log("[get_user_by_siga_id] email: ".$email, "info", false);
        
        if (!$email){
            $this->create_log("[get_user_by_siga_id] siga_id ".$siga_id." not found", "error", false);

            throw new Exception("siga_id ".$siga_id." not found");
        }
        
        $sql = "SELECT ID FROM wp_users WHERE user_email = '%s' LIMIT 1";
        $query = $wpdb->prepare($sql, $email);
        $result = $wpdb->get_results($query);
        
        if (sizeof($result) == 0){
            $this->create_log("[get_user_by_siga_id] user not found", "error", false);

            throw new Exception("user not found");
        }
        
        return $result[0]->ID;
    }

    /**
     * Obtem o ID de um úsuario a partir do ID do bitrix.
     * Caso não seja encontrado, uma exçessão é lancada.
     * @param int $bitrix_id ID no bitrix
     * @return int ID de úsuario
    */
    public function get_user_by_bitrix_id(int $bitrix_id): int{
        global $wpdb;

        $sql = "SELECT ID FROM wp_users WHERE ID IN (SELECT user_id FROM wp_usermeta WHERE meta_key = 'u_id_no_bitrix' AND meta_value = '%d') LIMIT 1";
        $query = $wpdb->prepare($sql, $bitrix_id);
        $result = $wpdb->get_results($query);

        if (sizeof($result) == 0){
            $this->create_log("[get_user_by_bitrix_id] user not found with bitrix_id ".$bitrix_id, "error", false);

            throw new Exception("user not found");
        }

        $this->create_log("[get_user_by_bitrix_id] user with ID ".$result[0]->ID." found", false);

        return $result[0]->ID;
    }

    /**
     * Obtem o ID de post type a partir do ID de úsuario.
     * Caso não seja encontrado, uma exçessão é lancada.
     * @param int $user_id ID do úsuario
     * @return int ID de post type
    */
    public function get_siga_id_by_user_id(int $user_id): int{
        global $wpdb;

        $user = get_user_by("id", $user_id);

        if (!$user) {
            $this->create_log("[get_siga_id_by_user_id] user with id ".$user_id." not found", "error", false);

            throw new Exception("user with id ".$user_id." not found");
        }

        $sql = "SELECT ID FROM wp_posts WHERE ID IN (SELECT post_id FROM wp_postmeta WHERE meta_key = 'c_email' AND meta_value = '%s') LIMIT 1";
        $query = $wpdb->prepare($sql, $user->user_email);
        $result = $wpdb->get_results($query);

        if (sizeof($result) == 0){
            $this->create_log("[get_siga_id_by_user_id] user with id ".$user_id." not found", "error", false);

            throw new Exception("siga id for user ".$user_id." not found");
        }

        return $result[0]->ID;
    }

    /**
     * Obtem o ID do bitrix a partir do ID de úsuario.
     * @param int $user_id ID do úsuario
     * @return int ID do bitrix
    */
    public function get_bitrix_by_user_id(int $user_id): ?int{
        $id = intval(get_user_meta($user_id, "u_id_no_bitrix", true));

        $this->create_log("[get_bitrix_by_user_id] obtained bitrix id ".$id." by user id ".$user_id, "info", false);

        return $id;
    }
  
    /**
     * Obtem as informações de úsuario no bitrix a partir do email
     * @param string $email
     * @return array|null 
    */
    public function get_bitrix_user(string $email): ?array{
        $result = $this->fetch_bitrix("https://savecash.bitrix24.com.br/rest/12291/2rugktp4u252wwtg/user.get.json?filter[EMAIL]=$email");

        $this->create_log("[get_bitrix_user] user got: ".json_encode($result["result"][0]), "info", false);

        return $result["result"][0];
    }
    
    /**
     * Obtem um departmento verificando um email no final do nome do departamento seguido de um espaço em branco
     * @param string $email
     * @return array|null 
    */
    public function get_user_department_by_email(string $email): ?array{
        $url = "https://zillesg.bitrix24.com.br/rest/5/wq5ux4eguljfyv3t/department.get.json?filter[NAME]=%%20$email";
        $result = $this->fetch_bitrix($url);

        $this->create_log("[get_user_department_by_email] Department got: ".json_encode($result["result"][0], JSON_UNESCAPED_UNICODE), "info", false);

        return $result["result"][0];
    }

    /**
     * Obtem um departmento pelo id
     * @param int $id
     * @return array|null 
    */
    public function get_user_department_by_id(int $id): ?array{
        $url = "https://zillesg.bitrix24.com.br/rest/5/wq5ux4eguljfyv3t/department.get.json?ID=$id";
        $result = $this->fetch_bitrix($url);

        $this->create_log("[get_user_department_by_id] Department got: ".json_encode($result["result"][0] ?? "[nenhum]", JSON_UNESCAPED_UNICODE), "info", false);

        return $result["result"][0];
    }
    
    /**
     * Faz requisições e devolve como um array associativo
     * @param string $url
     * @param string $args Ainda não existem argumentos a serem usados
     * @return mixed
    */
    public function fetch_bitrix(string $url): ?array{

        $this->create_log("[Fetching] URL: ".$url, "info", false);
        
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($curl);
        curl_close($curl);

        $this->create_log("[Fetching] Response: ".$response, "info", false);

        $result = json_decode($response, true);
        
        if ($result["error"]) {
            $this->create_log("For URL ".$url."\n".$result["error_description"], "error");
        }

        return $result;
    }

    /**
     * Faz requisições POST e devolve como um array associativo com o resultado
     * @param string $url
     * @param array $post_fields Array associativo com valores a serem passado no body da requisição
     * @return mixed
    */
    public function post_bitrix(string $url, array $post_fields){
        $curl = curl_init();

        $this->create_log("[POST Fetching] URL: ".$url, "info", false);

        curl_setopt_array($curl, array(
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_POSTFIELDS => http_build_query($post_fields),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($response, true);

        $this->create_log("[POST Fetching] Response: ".json_encode($response, JSON_UNESCAPED_UNICODE), "info", false);

        if ($result["error"]) {
            $this->create_log("For URL ".$url."\n".$result["error_description"], "error");
        }

        return $result;
    }

    /**
     * Registra um úsuario novo no bitrix
     * @param string $name
     * @param array $email
     * @param array $role Cargo. Pode ser `diretor`|`lider`|`gerente`|`supervisor`|`consultor`
     * @return int|null
    */
    public function register_bitrix_user(string $name, string $email, string $role, ?int $department): ?int{
        $name_array = explode(" ", trim($name));
        $first_name = $name_array[0];
        $last_name = join(" ", array_slice($name_array, 1)); 

        $url = "https://savecash.bitrix24.com.br/rest/5/qdolajv5wj7s15su/user.add.json?ACTIVE=true&NAME=$first_name&LAST_NAME=$last_name&EMAIL=$email&WORK_POSITION=$role&UF_DEPARTMENT=$department";
        $result = $this->fetch_bitrix($url);

        $this->create_log("[register_bitrix_user] New bitrix user created for ".$name." with id ".($result["result"] ?? "[nenhum]"), "info");

        return $result["result"];
    }

    /**
     * Registra um departamento
     * @param string $name nome do novo departamento
     * @param array $parent_department_id ID do departamento pai
     * @return int|null ID do departamento criado
    */
    public function register_user_department(string $name, int $parent_department_id): ?int {
        $result = $this->fetch_bitrix("https://savecash.bitrix24.com.br/rest/5/k68ksbh2fc28pzul/department.add.json?NAME=$name&PARENT=$parent_department_id");

        $this->create_log("[register_user_department] New department created for ".$name." with id ".$parent_department_id, "info", false);

        return $result["result"] ?? null;
    }

    /**
     * Muda o departamento de um úsuario
     * @param int $bitrix_id
     * @param int $department_id ID departamento que o úsuario irá
     * @param string Posição do usuario no departamento
     * @return array|null  
    */
    public function update_user_department(int $bitrix_id, int $department_id, string $role): ?array{
        $result = $this->fetch_bitrix("https://savecash.bitrix24.com.br/rest/12291/cnqcgclu356flgr3/user.update.json?ID=$bitrix_id&UF_DEPARTMENT=$department_id&WORK_POSITION=$role");

        $this->create_log("[update_user_department] Department updated for bitrix id ".$bitrix_id." and uf_department ".$department_id, "info", false);

        return $result;
    }

    /**
     * Obtem todos os Deals de um úsuario
     * @param int $bitrix_id
     * @return array|null Lista de deals 
    */
    public function get_deals_by_bitrix_id(int $bitrix_id): ?array{
        $result = $this->fetch_bitrix("https://savecash.bitrix24.com.br/rest/5/wlc7nshosnel7yev/crm.deal.list.json?filter[ASSIGNED_BY_ID]=$bitrix_id");

        $this->create_log("[get_deals_by_bitrix_id] Deals: ".json_encode($result, JSON_UNESCAPED_UNICODE), "info", false);

        return $result["result"];
    }

    /**
     * Obtem todos os Deals com um nome exato
     * @param string $name Nome do(s) Deal(s)
     * @return array|null Lista de deals
    */
    public function get_deals_by_name(string $name): ?array{
        $result = $this->fetch_bitrix("https://savecash.bitrix24.com.br/rest/5/wlc7nshosnel7yev/crm.deal.list.json?filter[TITLE]=$name");

        $this->create_log("[get_deals_by_name] Obtained: ".json_encode($result, JSON_UNESCAPED_UNICODE), "info", false);

        return $result["result"];
    }

    /**
     * Deleta um Deal
     * @param int $deal_id ID do deal
     * @return bool `true` em caso de successo, `false` em caso de falha
    */
    public function delete_deal(int $deal_id){
        $result = $this->fetch_bitrix("https://savecash.bitrix24.com.br/rest/5/0txm7hmniw1y66hj/crm.deal.delete.json?ID=$deal_id");

        $this->create_log("[delete_card] Deal with id ".$deal_id." deleted", "info", false);

        return $result["result"][0];
    }

    /**
     * Designa um úsuario á um deal
     * @param int $deal_id ID do deal
     * @param int $bitrix_id ID do usuario
     * @return bool `true` em caso de successo, `false` em caso de falha
    */
    public function assign_user_to_deal(int $deal_id, int $bitrix_id){
        $result = $this->fetch_bitrix("https://savecash.bitrix24.com.br/rest/5/8efktob9v8sem6ma/crm.deal.update.json?ID=$deal_id&ASSIGNED_BY_ID=$bitrix_id");

        $this->create_log("[assign_user_to_deal] Deal with id ".$deal_id." assigned to user_id ".$bitrix_id, "info", false);

        return $result["result"][0];
    }

    /**
     * Designa um úsuario á um deal
     * @param string $name - Nome do Deal
     * @param int $bitrix_id ID do usuario
     * @param array $args argumentos para criação de um deal. Por padrão configurado para criar na categoria 17.    
     * 
     * Veja os argumentos na [documentação](https://training.bitrix24.com/rest_help/crm/deals/crm_deal_add.php)
    */
    public function create_deal(string $name, int $bitrix_id, ?array $args = []): ?int{
        $result = $this->post_bitrix(
            "https://savecash.bitrix24.com.br/rest/5/3afdy25dz6672b50/crm.deal.add.json",
            [
            'fields' => [
                "TITLE"                 => $name,
                "CATEGORY_ID"           => $args["category_id"] ?? 17,
                "STAGE_ID"              => $args["stage_id"] ?? "C17:NEW",
                "OPENED"                => $args["opened"] ?? "Y",
                "ASSIGNED_BY_ID"        => $bitrix_id,
                "TYPE_ID"               => $args["type_id"] ?? "GOODS",
                "PROBABILITY"           => $args["probability"] ?? "",
                "BEGINDATE"             => date('Y-m-d H:i:s'),
                "CLOSEDATE"             => date('Y-m-d H:i:s'),
                "OPPORTUNITY"           => $args["opportunity"] ?? 0,
                "CURRENCY_ID"           => $args["currency_id"] ?? "BRL",
                "UF_CRM_1650365006867"  => $args["uf_crm_code"] ?? "https://academia.savecash.com.br",
                "COMMENTS"              => $args["comments"] ?? "Comentário"
            ],
            'params' => [
                "REGISTER_SONET_EVENT" => $args["register_sonet_event"] ?? "N"
            ]
        ]);

        $this->create_log("[create_deal] Deal \"$name\" created", "info", false);

        return intval($result["result"]);
    }

    /**
     * Atualiza as informações de atualização no bitrix de um candidato
     * @param int $siga_id - ID do post type do candidato
     * @param bool $accepted_invitation `true` para "sim", `false` para "não"
     * @param int $bitrix_id ID no bitrix
     * 
     * @return array Dados da atualização
    */
    public function update_candidate(int $user_id, $accepted_invitation, int $bitrix_id): array{
        update_user_meta($user_id, 'u_bitrix_sync', time());
        update_user_meta($user_id, 'u_aceitou_o_convite', boolval($accepted_invitation) ? "sim" : "não");
        update_user_meta($user_id, 'u_id_no_bitrix', $bitrix_id);
        
        $this->create_log("[update_candidate] Candidate with user_id $user_id updated at ".time(), "info", false);

        return [
            "timestamp" => time(),
            "invitation" => boolval($accepted_invitation) ? "sim" : "não",
            "id" => $bitrix_id
        ];
    }

    /**
     * Edita um úsuario no bitrix
     * @param string $id
     * @param array $email
     * @return array|null
    */

     public function update_user_on_bitrix(int $id, ?string $email): ?array{
        $user = [];

       
           //https://savecash.bitrix24.com.br/rest/12291/l7oj1nif5ctmumtr/user.update.json?
            $url = "https://savecash.bitrix24.com.br/rest/5/pt8f7m0ry4sidehx/user.update.json?ID=$id&EMAIL=$email";
            $result = $this->fetch_bitrix($url);

            return $result;
            
    
            $this->create_log("[update_user_on_bitrix] data collected: " . json_encode($user, JSON_UNESCAPED_UNICODE), "info", false);
        


     }

    /**
     * Obter o ID do recrutador
     * @param string $recruiter_email - Email do recrutador
     * @return int|null ID do post type do recrutador
    */
    public function get_recruiter_id(string $recruiter_email): ?int{
        global $wpdb;

        $sql = "SELECT ID FROM wp_posts WHERE ID = (SELECT post_id FROM wp_postmeta WHERE meta_key = 'c_email' AND meta_value = '%s')";
        $query = $wpdb->prepare($sql, $recruiter_email);
        $result = $wpdb->get_results($query);

        if (sizeof($result) == 0){
            $this->create_log("[get_recruiter_id] No recruiter found for email ".$recruiter_email, "info", false);
            return null;
        }

        $this->create_log("[get_recruiter_id] Recruiter id: ".$result[0]->ID, "info", false);

        return $result[0]->ID;
    }

    /**
     * Define um úsuario como responsável por um departmento
     * @param int $bitrix_id ID do úsuario
     * @param int $department_id ID do departamento a assumir resposabilidade
     * @return bool `true` quando bem sucessido. `false` quando falhar.
    */
    public function set_head_of_department(int $bitrix_id, int $department_id): ?bool {
        $result = $this->fetch_bitrix("https://savecash.bitrix24.com.br/rest/5/qxur4b9a8wd4ydyq/department.update.json?UF_HEAD=$bitrix_id&ID=$department_id");

        $this->create_log("[set_head_of_department] user with id $bitrix_id are now head of department $department_id", "info", false);

        return $result["result"];
    }

    /**
     * Obtem departamentos filhos diretos de um departamento
     * @param int $department_id ID do departamento
     * @return array lista de departamentos
    */
    public function get_child_departments(int $department_id): ?array{
        $result = $this->fetch_bitrix("https://savecash.bitrix24.com.br/rest/5/vsjaeeetcxavdtx9/department.get.json?filter[PARENT]=$department_id");

        $this->create_log("[get_child_departments] departments got!", "info", false);

        return $result["result"];
    }

    /**
     * Obtem os departamentos que tem um email especifico no final do nome do departamento
     * @param string $email 
     * @return array departamentos
    */
    public function get_departments_by_email_in_name(string $email) {
        $result = $this->fetch_bitrix("https://savecash.bitrix24.com.br/rest/5/vsjaeeetcxavdtx9/department.get.json?filter[PARENT]=%%20$email");

        $this->create_log("[get_departments_by_email_in_name] departments got!", "info", false);

        return $result["result"];
    }

    /**
     * Designa um novo pai para um departamento
     * @param int $department_id ID do departamento
     * @param int $department_id ID do departamento a ser o pai
    */
    public function update_department_parent(int $department_id, int $parent_department_id): ?array{
        $result = $this->fetch_bitrix("https://savecash.bitrix24.com.br/rest/5/0m040o9i0y2m8g3b/department.update.json?ID=$department_id&PARENT=$parent_department_id");

        $this->create_log("[update_department_parent] department updated!", "info", false);

        return $result;
    }

    /**
     * Desativa um úsuario
     * @param int $bitrix_id ID do úsuario
    */
    public function deactivate_user(int $bitrix_id){
        $result = $this->fetch_bitrix("https://savecash.bitrix24.com.br/rest/12291/cnqcgclu356flgr3/user.update.json?ID=$bitrix_id&ACTIVE=0");

        $this->create_log("[deactivate_user] User deactivated ".$bitrix_id, "info", false);

        return $result;
    }

    /**
     * Deleta um departamento
     * @param int $bitrix_id ID do úsuario
    */
    public function delete_department(int $department_id){
        $result = $this->fetch_bitrix("https://savecash.bitrix24.com.br/rest/5/zm4box7j6yfi877w/department.delete.json?ID=$department_id");

        $this->create_log("[delete_department] department deleted ".$department_id, "info", false);

        return $result;
    }
}

