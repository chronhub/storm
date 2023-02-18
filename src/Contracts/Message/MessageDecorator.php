<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Message;

use Chronhub\Storm\Message\Message;

interface MessageDecorator
{
    public function decorate(Message $message): Message;
}
