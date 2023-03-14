<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use stdClass;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Contracts\Message\Header;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Message\Decorator\EventType;

#[CoversClass(EventType::class)]
final class EventTypeTest extends UnitTestCase
{
    #[Test]
    public function it_set_event_type_to_message_headers(): void
    {
        $messageDecorator = new EventType();

        $message = new Message(new stdClass());

        $this->assertEmpty($message->headers());

        $decoratedMessage = $messageDecorator->decorate($message);

        $this->assertArrayHasKey(Header::EVENT_TYPE, $decoratedMessage->headers());

        $this->assertEquals(stdClass::class, $decoratedMessage->header(Header::EVENT_TYPE));
    }

    #[Test]
    public function it_does_not_set_event_id_to_message_headers_if_already_exists(): void
    {
        $messageDecorator = new EventType();

        $message = new Message(new stdClass(), [Header::EVENT_TYPE => 'some_event_type']);

        $decoratedMessage = $messageDecorator->decorate($message);

        $this->assertEquals('some_event_type', $decoratedMessage->header(Header::EVENT_TYPE));
    }
}
