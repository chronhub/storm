<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Contracts\Message\MessageDecorator;
use Chronhub\Storm\Message\ChainMessageDecorator;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ChainMessageDecorator::class)]
final class ChainMessageDecoratorTest extends UnitTestCase
{
    public function testDecorateMessage(): void
    {
        $decorator = new class implements MessageDecorator
        {
            public function decorate(Message $message): Message
            {
                return $message->withHeader('some', 'header');
            }
        };

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']));

        $this->assertEmpty($message->headers());

        $chain = new ChainMessageDecorator($decorator);

        $decoratedMessage = $chain->decorate($message);

        $this->assertNotSame($message, $decoratedMessage);
        $this->assertSame(['some' => 'header'], $decoratedMessage->headers());
    }

    public function testDecorateMessageWithManyDecorators(): void
    {
        $decorator1 = new class implements MessageDecorator
        {
            public function decorate(Message $message): Message
            {
                return $message->withHeader('some', 'header');
            }
        };

        $decorator2 = new class implements MessageDecorator
        {
            public function decorate(Message $message): Message
            {
                return $message->withHeader('another', 'header');
            }
        };

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']));

        $this->assertEmpty($message->headers());

        $chain = new ChainMessageDecorator($decorator1, $decorator2);

        $decoratedMessage = $chain->decorate($message);

        $this->assertNotSame($message, $decoratedMessage);
        $this->assertSame(['some' => 'header', 'another' => 'header'], $decoratedMessage->headers());
    }
}
