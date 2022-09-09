<?php

interface BitrixSyncStrategy
{
    public function sync(
        int $id,
        string $id_type = "siga",
        ?array $options = []
    ): array;
}
