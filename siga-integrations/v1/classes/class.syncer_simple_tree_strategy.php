<?php

include_once __DIR__ . "/class.bitrix.php";
include_once __DIR__ . "/class.candidate_controller.php";

class syncerSimpleTreeStrategy
{
    private $last_candidate_sincronized;

    public function sync(
        int $id,
        string $id_type = "siga",
        ?array $options = [
            "debug" => false,
            "root_department" => 23115
        ]
    ): array {
        $bitrix = new Bitrix($options["debug"]);
        $candidate_controller = new CandidateController();

        $candidate_data = $bitrix->get_user_data($id, $id_type);
 
        if (sizeof($candidate_data) == 0) {
            $bitrix->create_log("[sync_bitrix] user data not found", "error");

            throw new Exception("user data not found");
        }

        if (is_null($candidate_data["bitrix_user"])) {
            $new_id = $bitrix->register_bitrix_user(
                $candidate_data["name"],
                $candidate_data["email"],
                $candidate_data["role"],
                $candidate_data["department"] ?? $options["root_department"]
            );

            if ($options["create_card"]) {
                $onboard_cards_id = $bitrix->get_deals_by_bitrix_id(intval($candidate_data["bitrix_user"]["ID"]));
    
                if (sizeof($onboard_cards_id) == 0) {
                    $bitrix->create_deal(
                        $candidate_data["name"],
                        intval($candidate_data["bitrix_user"]["ID"])
                    );
                }
            }

            $candidate_data["bitrix_user"] = ["ID" => $new_id["result"]];
        }
 
        $superiors = $candidate_controller->get_superiors_recursive(
            $candidate_controller->get_candidate_by_id($id)
        );

        foreach ($superiors as $superior) {
            // atualizar o colocando como departamento pai o departamento do diretor (apenas se for contratado)
        }

        $candidate_data["updates"] = $bitrix->update_candidate(
            intval($candidate_data["siga_id"]),
            $candidate_data["accepted_invitation"],
            intval($candidate_data["bitrix_user"]["ID"])
        );

        return $candidate_data;
    }
}
