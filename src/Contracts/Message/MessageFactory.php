<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Message;

use Chronhub\Storm\Message\Message;

interface MessageFactory
{
    /**
     * Create a valid message instance
     *
     * @return Message
     */
    public function __invoke(object|array $message): Message;
}
