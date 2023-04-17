<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Message\Decorator\EventType;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;

#[CoversClass(EventType::class)]
final class EventTypeTest extends UnitTestCase
{
    public function testAddEventTypeToHeader(): void
    {
        $messageDecorator = new EventType();

        $message = new Message(new stdClass());

        $this->assertEmpty($message->headers());

        $decoratedMessage = $messageDecorator->decorate($message);

        $this->assertArrayHasKey(Header::EVENT_TYPE, $decoratedMessage->headers());

        $this->assertSame(stdClass::class, $decoratedMessage->header(Header::EVENT_TYPE));
    }

    public function testDoesNotAddEventTypeToHeaderIfAlreadyExists(): void
    {
        $messageDecorator = new EventType();

        $message = new Message(new stdClass(), [Header::EVENT_TYPE => 'some_event_type']);

        $decoratedMessage = $messageDecorator->decorate($message);

        $this->assertSame('some_event_type', $decoratedMessage->header(Header::EVENT_TYPE));
    }
}
