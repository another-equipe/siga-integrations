<?php

define("SIGA_API_SECRET", 'hWmYq3t6w9z$C&F)J@NcRfUjXn2r4u7x');
define("DEFAULT_API_PREFIX", "siga");
define("DEFAULT_API_VERSION", 1);

define("SIGA_V1_ROUTES", [
    "get:candidates" => [
        "route" => "/candidates",
        "method" => "GET"
    ],
    "get:candidate" => [
        "route" => "/candidates/(?P<phone>\d+)",
        "method" => "GET"
    ],
    "post:candidate" => [
        "route" => "/candidate",
        "method" => "POST"
    ],
    "post:bitrix" => [
        "route" => "/bitrix",
        "method" => "POST"
    ],
    "get:candidate_signs" => [
        "route" => "/candidates/signs",
        "method" => "GET"
    ],
    "get:candidate_sign" => [
        "route" => "/candidates/signs/(?P<id>\d+)",
        "method" => "GET"
    ],
    "post:create_academia_user" => [
        "route" => "/academia/user/(?P<id>\d+)",
        "method" => "POST"
    ],
    "post:course_completed" => [
        "route" => "/academia/user/course",
        "method" => "POST"
    ],
    "post:certificate_awarded" => [
        "route" => "/academia/user/certificate",
        "method" => "POST"
    ],
    "get:team" => [
        "route" => "/team/(?P<id>\d+)",
        "method" => "GET"
    ]
]);

define("CANDIDATES_SIGNS_SLUG", "savesign_candidatos");
define("CANDIDATES_SLUG", "c_consultores");
define("SCHEDULE_SEND_DISTRACT_SLUG", "cron_send_distract");
define("SCHEDULE_SEND_SCP_CONTRACT_SLUG", "cron_send_scp");
define("SCHEDULE_SEND_ATTACHMENT_CONTRACT_SLUG", "cron_send_att_contra");
define("SCHEDULE_SYNC_BITRIX_SLUG", "cron_sync_bitrix");
define("SCHEDULE_DELETE_DUPLICATE_CARD", "cron_del_dupli_cards");

define("ACADEMIA_DOMAIN", "https://api-lw15.learnworlds.com");
define("ACADEMIA_ACCESS_TOKEN", "TZXkqDX9nxrivB6pNFf3RJNIjHyncLcL3ch1IPXA"); //valid until 12/07/2023
define("ACADEMIA_CLIENT_ID", "62cd81001c440f668b022ed2");
define("ACADEMIA_DEFAULT_USER_PASS", "savecash@2022");

define("DEFAULT_NEW_HISTORIC_PARAMS", [
    "title" => "untitled",
    "action" => "unknown",
    "who_received" => "nameless",
    "recruiter" => "",
    "old_recruiter" => ""
]);
