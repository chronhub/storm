<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Producer;

use Chronhub\Storm\Message\Message;

interface MessageProducer
{
    public function produce(Message $message): Message;
}
