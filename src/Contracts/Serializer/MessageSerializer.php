<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Serializer;

use Chronhub\Storm\Contracts\Reporter\Reporting;
use Chronhub\Storm\Message\Message;

interface MessageSerializer
{
    /**
     * @return array{'headers':array<string, mixed>, 'content':array<string, mixed>}
     */
    public function serializeMessage(Message $message): array;

    public function deserializePayload(array $payload): Reporting;
}
