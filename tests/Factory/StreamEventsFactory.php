<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Factory;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use DateTimeImmutable;
use Generator;

use function count;

class StreamEventsFactory
{
    protected static string $event = SomeEvent::class;

    /**
     * Return a cloned factory with the event given
     *
     * @param class-string<DomainEvent> $event
     */
    public static function withEvent(string $event): self
    {
        $self = new static();

        $self::$event = $event;

        return $self;
    }

    /**
     * @param  array<DomainEvent>     $events
     * @return Generator<DomainEvent>
     */
    public static function fromArray(array $events): Generator
    {
        foreach ($events as $event) {
            yield $event;
        }

        return count($events);
    }

    /**
     * @return Generator<empty>
     */
    public static function fromEmpty(): Generator
    {
        yield from [];

        return 0;
    }

    /**
     * Fake an empty generator
     *
     * @return Generator<empty>
     */
    public static function fromEmptyAndRaiseStreamNotFoundException(string $streamName): Generator
    {
        yield throw StreamNotFound::withStreamName(new StreamName($streamName));
    }

    /**
     * @param  positive-int           $endAt
     * @param  positive-int           $startAt
     * @return Generator<DomainEvent>
     */
    public static function fromInternalPosition(int $endAt, int $startAt = 1): Generator
    {
        $count = 1;
        while ($count < $endAt + 1) {
            $count++;

            yield self::makeEvent([EventHeader::INTERNAL_POSITION => $startAt++]);
        }

        return $endAt;
    }

    /**
     * @param positive-int|null $internalPosition
     */
    public static function withHeaders(
        DateTimeImmutable|string $eventTime = null,
        int $internalPosition = null,
        array $content = []
    ): DomainEvent {

        $headers = [];
        $internalPosition and $headers[EventHeader::INTERNAL_POSITION] = $internalPosition;
        $eventTime and $headers[Header::EVENT_TIME] = $eventTime;

        return self::makeEvent($headers, $content);
    }

    protected static function makeEvent(array $headers, array $content = []): DomainEvent
    {
        /** @var DomainEvent $event */
        $event = static::$event;

        return $event::fromContent($content)->withHeaders($headers);
    }
}
