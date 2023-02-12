<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter\Exceptions;

class MessageNotHandled extends RuntimeException
{
    public static function withMessageName(string $messageName): self
    {
        return new self("Message with name $messageName was not handled");
    }
}
