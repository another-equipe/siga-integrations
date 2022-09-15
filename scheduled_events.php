<?php

include_once __DIR__."/events/send_distract.php";
include_once __DIR__."/events/send_scp_contract.php";
include_once __DIR__."/events/send_attachment_contract.php";
include_once __DIR__."/events/orfao.php";
include_once __DIR__."/events/email_catho.php";
include_once __DIR__."/events/sync_bitrix.php";
include_once __DIR__."/events/webhooks.php";

$environment = strpos($_SERVER['HTTP_HOST'], 'dev') ? "DEV" : "PRODUCTION";

add_action('wp_cron_send_distracts', 'send_distract');
add_action('wp_cron_send_scp_contracts', 'send_scp_contract');
add_action('wp_cron_send_attachment_contract', 'send_attachment_contract');
//add_action('wp_cron_email_catho', 'orfao');
//add_action('wp_cron_orfao', 'email_catho');
//add_action('wp_cron_webhooks', 'send_webhook_event');

if ($environment == "PRODUCTION"){
    // apenas rodado em producao

    //add_action('wp_cron_bitrix', 'sync_bitrix');
}