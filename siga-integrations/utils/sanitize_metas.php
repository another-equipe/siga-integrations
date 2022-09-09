<?php

function sanitize_metas($post_metas){

    if (is_null($post_metas)) return null;

    $sanitized_post = [];

    foreach($post_metas as $key => $value) {
        $sanitized_post[$key] = (sizeof($value) > 1) ? $value : $value[0];
    }

    return $sanitized_post;
}