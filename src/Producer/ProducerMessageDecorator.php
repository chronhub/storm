<?php

declare(strict_types=1);

namespace Chronhub\Storm\Producer;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Message\MessageDecorator;
use Chronhub\Storm\Message\Message;

final readonly class ProducerMessageDecorator implements MessageDecorator
{
    public function __construct(private ProducerStrategy $producerStrategy)
    {
    }

    public function decorate(Message $message): Message
    {
        if ($message->hasNot(Header::EVENT_STRATEGY)) {
            $message = $message->withHeader(Header::EVENT_STRATEGY, $this->producerStrategy->value);
        }

        if ($message->hasNot(Header::EVENT_DISPATCHED)) {
            $message = $message->withHeader(Header::EVENT_DISPATCHED, false);
        }

        return $message;
    }
}
