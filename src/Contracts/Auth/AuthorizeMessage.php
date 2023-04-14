<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Auth;

use Chronhub\Storm\Message\Message;

interface AuthorizeMessage
{
    public function isGranted(string $messageName, Message $message, mixed $context = null): bool;

    public function isNotGranted(string $messageName, Message $message, mixed $context = null): bool;
}
