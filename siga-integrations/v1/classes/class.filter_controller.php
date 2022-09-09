<?php

class FilterController{
    private $filters;

    public function __construct($filters){
        $this->filters = $filters;
    }

    public function filter_post_metas(array $post_meta): array {
        return $post_meta;
    }
}