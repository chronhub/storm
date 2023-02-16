<?php

declare(strict_types=1);

namespace Chronhub\Storm\Message;

use Chronhub\Storm\Contracts\Message\MessageDecorator;

final class NoOpMessageDecorator implements MessageDecorator
{
    public function decorate(Message $message): Message
    {
        return $message;
    }
}
