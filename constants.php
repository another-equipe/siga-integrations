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
define("WEBHOOKS_SLUG", "sc_integration");
define("SCHEDULE_SEND_DISTRACT_SLUG", "cron_send_distract");
define("SCHEDULE_SEND_SCP_CONTRACT_SLUG", "cron_send_scp");
define("SCHEDULE_SEND_ATTACHMENT_CONTRACT_SLUG", "cron_send_att_contra");
define("SCHEDULE_SYNC_BITRIX_SLUG", "cron_sync_bitrix");
define("SCHEDULE_SEND_EMAIL_CATHO", "cron_email_catho");
define("SCHEDULE_ORFAO", "cron_email_catho");
define("SCHEDULE_WEBHOOKS", "cron_webhooks_events");

define("SAVESIGN_DISTRACT_MODEL_ID", "6315dcd2d414b");
define("SAVESIGN_SCP_MODEL_ID", "6320cd79e162d");
define("SAVESIGN_ATTACHMENT_CONSULTOR_CONTRACT_MODEL_ID", "6320cd4a2c70e");
define("SAVESIGN_ATTACHMENT_SUPERVISOR_CONTRACT_MODEL_ID", "6320cd62e1cf8");
define("SAVESIGN_ATTACHMENT_MANAGER_CONTRACT_MODEL_ID", "6320cd13ccfe1");
define("SAVESIGN_ATTACHMENT_LID_CONTRACT_MODEL_ID", "6320ccf7b4282");
define("SAVESIGN_ATTACHMENT_DIRECTOR_CONTRACT_MODEL_ID", "63111ce57362d");

define("ACADEMIA_DOMAIN", "https://api-lw15.learnworlds.com");
define("ACADEMIA_ACCESS_TOKEN", "TZXkqDX9nxrivB6pNFf3RJNIjHyncLcL3ch1IPXA"); //valid until 12/07/2023
define("ACADEMIA_CLIENT_ID", "62cd81001c440f668b022ed2");
define("ACADEMIA_DEFAULT_USER_PASS", "savecash@2022");

define('SECRET_KEY_SC', 'hWmYq3t6w9z$C&F)J@NcRfUjXn2r4u7x');
define("ARMANDO_WEBHOOK_KEY", "kEZr9tupuCkHm4BPnKtqsPV9hJXRL5f4");

define("CANDIDATE_HIERARQUIES", [
    "diretor" => 0,
    "lider" => 1,
    "gerente" => 2,
    "supervisor" => 3,
    "consultor" => 4
]);	

define("DEFAULT_ROOT_DEPARTMENT", 325);

define("WEBHOOK_URL", "https://webhook.site/ceba4161-77bc-4d4a-b63e-938cc85b194e");   // url de teste
/* 
if (function_exists("getEnvVar")) {
    echo ".";
} */