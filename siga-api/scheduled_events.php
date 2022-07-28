<?php

include_once __DIR__."/events/send_distract.php";
include_once __DIR__."/events/send_scp_contract.php";
include_once __DIR__."/events/send_attachment_contract.php";
include_once __DIR__."/events/sync_bitrix.php";

$environment = strpos($_SERVER['HTTP_HOST'], 'dev') ? "DEV" : "PRODUCTION";

add_action('wp_cron_send_distracts', 'send_distract');
add_action('wp_cron_send_scp_contracts', 'send_scp_contract');
add_action('wp_cron_send_attachment_contract', 'send_attachment_contract');

if ($environment == "PRODUCTION"){
    add_action('wp_cron_sync_bitrix', 'sync_bitrix');
}