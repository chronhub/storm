<?php

declare(strict_types=1);

namespace Chronhub\Storm\Message\Decorator;

use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Message\UniqueId;
use Chronhub\Storm\Contracts\Message\MessageDecorator;

final readonly class EventId implements MessageDecorator
{
    public function __construct(private UniqueId $uniqueId)
    {
    }

    public function decorate(Message $message): Message
    {
        if ($message->hasNot(Header::EVENT_ID)) {
            $message = $message->withHeader(Header::EVENT_ID, $this->uniqueId->generate());
        }

        return $message;
    }
}
