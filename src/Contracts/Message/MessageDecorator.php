<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Message;

use Chronhub\Storm\Message\Message;

interface MessageDecorator
{
    /**
     * Decorate message headers
     *
     * @param  Message  $message
     * @return Message
     */
    public function decorate(Message $message): Message;
}
