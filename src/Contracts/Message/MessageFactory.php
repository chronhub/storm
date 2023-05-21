<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Message;

use Chronhub\Storm\Message\Message;

interface MessageFactory
{
    // todo name method create
    public function __invoke(object|array $message): Message;
}
