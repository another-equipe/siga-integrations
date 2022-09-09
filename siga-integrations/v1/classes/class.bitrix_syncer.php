<?php

include_once __DIR__ . "/interface.sync_strategy.php";

class BitrixSyncer {
    public function sync_candidate(BitrixSyncStrategy $sync_strategy): array{
        return $sync_strategy();
    }
}