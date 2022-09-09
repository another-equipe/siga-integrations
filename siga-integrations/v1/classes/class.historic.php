<?php

include_once __DIR__."/../../constants.php";
include_once __DIR__."/../../../../../modules/siga_new_historic.php";

class Historic {
    public function new(array $data): void{
        if (function_exists("newHistoric")){
            newHistoric(
                $data["title"],
                $data["action"],
                $data["user"],
                $data["who_received"],
                $data["recruiter"],
                $data["old_recruiter"]
            );
        }
    }
}