<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Message\EventId;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Message\UniqueIdV4;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;

#[CoversClass(EventId::class)]
final class EventIdTest extends UnitTestCase
{
    private UniqueIdV4 $uniqueId;

    protected function setUp(): void
    {
        $this->uniqueId = new UniqueIdV4();
    }

    public function testAddEventIdHeader(): void
    {
        $messageDecorator = new EventId($this->uniqueId);

        $message = new Message(new stdClass());

        $this->assertEmpty($message->headers());

        $decoratedMessage = $messageDecorator->decorate($message);

        $this->assertArrayHasKey(Header::EVENT_ID, $decoratedMessage->headers());

        $uidString = $decoratedMessage->header(Header::EVENT_ID);

        $this->assertTrue(Uuid::isValid($uidString));
        $this->assertInstanceOf(UuidV4::class, Uuid::fromString($uidString));
    }

    public function testDoesNotAddEventIdHeaderIfAlreadyExists(): void
    {
        $messageDecorator = new EventId($this->uniqueId);

        $message = new Message(new stdClass(), [Header::EVENT_ID => 'some_event_id']);

        $decoratedMessage = $messageDecorator->decorate($message);

        $this->assertSame('some_event_id', $decoratedMessage->header(Header::EVENT_ID));
    }
}
