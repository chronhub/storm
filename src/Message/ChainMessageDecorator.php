<?php

declare(strict_types=1);

namespace Chronhub\Storm\Message;

use Chronhub\Storm\Contracts\Message\MessageDecorator;

final readonly class ChainMessageDecorator implements MessageDecorator
{
    private array $messageDecorators;

    public function __construct(MessageDecorator ...$messageDecorators)
    {
        $this->messageDecorators = $messageDecorators;
    }

    public function decorate(Message $message): Message
    {
        foreach ($this->messageDecorators as $messageDecorator) {
            $message = $messageDecorator->decorate($message);
        }

        return $message;
    }
}
