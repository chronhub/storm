<?php

declare(strict_types=1);

namespace Chronhub\Storm\Message\Decorator;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Message\MessageDecorator;
use Chronhub\Storm\Message\Message;

final class EventType implements MessageDecorator
{
    public function decorate(Message $message): Message
    {
        if ($message->hasNot(Header::EVENT_TYPE)) {
            $message = $message->withHeader(Header::EVENT_TYPE, $message->event()::class);
        }

        return $message;
    }
}
