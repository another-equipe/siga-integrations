<?php

class Pagination {
    public function get_pagination_config($offset, $limit, $count){
        $request_url = strtok($_SERVER['REQUEST_SCHEME']."://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], "?");
        
        $pagination = [
            "offset" => intval($offset),
            "limit" => intval($limit),
        ];

        if ($offset == null || $offset < 0){
            $pagination["offset"] = 0;
        }

        if ($limit == null || $limit > 100 || $limit < 1) {
            $pagination["limit"] = 100;
        }

        $next = ($pagination["offset"] + $pagination["limit"] >= $count) ? 
            "" : 
            $pagination["offset"] + $pagination["limit"];

        $previous = ($pagination["offset"] - $pagination["limit"]) < 0 ?
            "" :
            $pagination["offset"] - $pagination["limit"];
        
        $pagination["next_url"] = (strlen($next) > 0) ?
            $request_url."?offset=".$next."&limit=".$pagination["limit"]."&key=".$_GET['key'] :
            null;
        $pagination["previous_url"] = (strlen($previous) > 0) ?
            $request_url."?offset=".$previous."&limit=".$pagination["limit"]."&key=".$_GET['key'] :
            null;

        return $pagination;
    }
}