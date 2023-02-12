<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Producer;

use Chronhub\Storm\Message\Message;

interface MessageQueue
{
    /**
     * Queue message to his dispatcher
     *
     * @param  Message  $message
     * @return void
     */
    public function toQueue(Message $message): void;
}
