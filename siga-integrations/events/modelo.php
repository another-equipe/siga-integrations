<?php

include_once __DIR__."/../constants.php";
include_once __DIR__."/queue_handler.php";

function send_distract(){
    global $wpdb;
    
    // pegar um item da fila de eventos
    $result = get_queue("nome_da_fila");
    $value = sizeof($result) > 0 ? $result[0]["id"] : null;
    
    if (is_null($value)) return;
    
    /*
    
    faça oque quiser com o valor atual da fila em $value...
    
    */
    
    // depois que o evento for processado, remova-o da fila
    remove_from_queue($value, "nome_da_fila");
}