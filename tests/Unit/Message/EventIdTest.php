<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use stdClass;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Message\UniqueIdV4;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Message\Decorator\EventId;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(EventId::class)]
final class EventIdTest extends UnitTestCase
{
    private UniqueIdV4 $uniqueId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uniqueId = new UniqueIdV4();
    }

    #[Test]
    public function it_set_event_id_to_message_headers(): void
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

    #[Test]
    public function it_does_not_set_event_id_to_message_headers_if_already_exists(): void
    {
        $messageDecorator = new EventId($this->uniqueId);

        $message = new Message(new stdClass(), [Header::EVENT_ID => 'some_event_id']);

        $decoratedMessage = $messageDecorator->decorate($message);

        $this->assertEquals('some_event_id', $decoratedMessage->header(Header::EVENT_ID));
    }
}
