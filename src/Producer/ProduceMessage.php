<?php

declare(strict_types=1);

namespace Chronhub\Storm\Producer;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Producer\MessageProducer;
use Chronhub\Storm\Contracts\Producer\MessageQueue;
use Chronhub\Storm\Contracts\Producer\ProducerUnity;
use Chronhub\Storm\Message\Message;

final readonly class ProduceMessage implements MessageProducer
{
    public function __construct(private ProducerUnity $unity,
                                private ?MessageQueue $enqueue)
    {
    }

    public function produce(Message $message): Message
    {
        $isSyncMessage = $this->unity->isSync($message);

        $message = $message->withHeader(Header::EVENT_DISPATCHED, true);

        if (! $isSyncMessage) {
            $this->enqueue->toQueue($message);
        }

        return $message;
    }
}
