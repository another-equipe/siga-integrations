<?php

include_once __DIR__."/../../constants.php";

class ClickSign{
    public function schedule_send_distract(int $id): bool{
        if (is_null($id)) return false;
        $result = update_post_meta($id, "c_contract_status", "send distract");

        return boolval($result);
    }
    
    public function schedule_send_SPC_contract(int $id): bool{
        if (is_null($id)) return false;
        $result = update_post_meta($id, "c_contract_status", "send contract");

        return boolval($result);
    }

    public function schedule_send_attachment_contract(int $id): bool{
        if (is_null($id)) return false;
        $result = update_post_meta($id, "c_contract_status", "send contract");

        return boolval($result);
    }
}