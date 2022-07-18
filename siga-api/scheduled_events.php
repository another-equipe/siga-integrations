<?php

include_once __DIR__."/events/send_distract.php";

add_action('wp_cron_send_distracts', 'send_distract');