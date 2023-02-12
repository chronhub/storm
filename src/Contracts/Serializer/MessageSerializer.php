<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Serializer;

use Generator;
use Chronhub\Storm\Message\Message;

interface MessageSerializer
{
    /**
     * Serialize Message
     *
     * @param  Message  $message
     * @return array{'headers':array<string, mixed>, 'content':array<string, mixed>}
     */
    public function serializeMessage(Message $message): array;

    /**
     * Unserialize content of message
     *
     * @param  array  $payload
     * @return Generator<object>
     */
    public function unserializeContent(array $payload): Generator;
}
