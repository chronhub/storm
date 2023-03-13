<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Serializer;

use Chronhub\Storm\Contracts\Reporter\Reporting;

interface ContentSerializer
{
    /**
     * Serialize Message event
     */
    public function serialize(Reporting $event): array;

    /**
     * Unserialize Message event
     */
    public function deserialize(string $source, array $payload): Reporting;
}
