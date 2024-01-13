<?php

declare(strict_types=1);

namespace Chronhub\Storm\Message;

use Chronhub\Storm\Contracts\Message\MessageFactory as Factory;
use Chronhub\Storm\Contracts\Serializer\MessageSerializer;

use function is_array;

final readonly class MessageFactory implements Factory
{
    public function __construct(private MessageSerializer $serializer)
    {
    }

    public function __invoke(object|array $message): Message
    {
        if (is_array($message)) {
            $message = $this->serializer->deserializePayload($message);
        }

        if ($message instanceof Message) {
            return new Message($message->event(), $message->headers());
        }

        return new Message($message);
    }
}
