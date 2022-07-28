<?php

include_once __DIR__."/../../constants.php";
include_once __DIR__."/../../../../../modules/siga_new_historic.php";

class Historic {
    public function new(array $data = DEFAULT_NEW_HISTORIC_PARAMS): void{
        if (function_exists("newHistoric")){
            newHistoric(
                $data["title"] ?? DEFAULT_NEW_HISTORIC_PARAMS["title"],
                $data["action"] ?? DEFAULT_NEW_HISTORIC_PARAMS["action"],
                $data["who_received"] ?? DEFAULT_NEW_HISTORIC_PARAMS["who_received"],
                $data["recruiter"] ?? DEFAULT_NEW_HISTORIC_PARAMS["recruiter"],
                $data["old_recruiter"] ?? DEFAULT_NEW_HISTORIC_PARAMS["old_recruiter"]
            );
        }
    }
}