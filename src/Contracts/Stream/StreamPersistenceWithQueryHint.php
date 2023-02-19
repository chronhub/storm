<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Stream;

interface StreamPersistenceWithQueryHint extends StreamPersistence
{
    public function indexName(string $tableName): ?string;
}
