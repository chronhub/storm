<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Message\EventTime;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Tests\UnitTestCase;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;

#[CoversClass(EventTime::class)]
class EventTimeTest extends UnitTestCase
{
    private MockObject|SystemClock $clock;

    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->clock = $this->createMock(SystemClock::class);
        $this->now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public function testAddEventTimeToHeader(): void
    {
        $this->clock
            ->expects($this->once())
            ->method('now')
            ->willReturn($this->now);

        $messageDecorator = new EventTime($this->clock);

        $message = new Message(new stdClass());

        $this->assertEmpty($message->headers());

        $decoratedMessage = $messageDecorator->decorate($message);

        $this->assertArrayHasKey(Header::EVENT_TIME, $decoratedMessage->headers());

        $this->assertSame($this->now, $decoratedMessage->header(Header::EVENT_TIME));
    }

    public function testDoesNotAddEventTimeHeaderIfAlreadyExists(): void
    {
        $pastEventTime = $this->now->sub(new DateInterval('PT1H'));

        $messageDecorator = new EventTime($this->clock);

        $message = new Message(new stdClass(), [Header::EVENT_TIME => $pastEventTime]);

        $decoratedMessage = $messageDecorator->decorate($message);

        $this->assertSame($pastEventTime, $decoratedMessage->header(Header::EVENT_TIME));
    }
}
