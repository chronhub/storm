<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Factory;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Clock\PointInTimeFactory;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use DateTimeImmutable;
use Generator;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;

use function count;

readonly class StreamEventsFactory
{
    public function __construct(public string $event = SomeEvent::class)
    {
    }

    /**
     * Return a cloned factory with the event given
     *
     * @param class-string<DomainEvent> $event
     */
    public static function withEvent(string $event): self
    {
        return new static($event);
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
     * Fake an empty generator and raise StreamNotFound exception
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
    public function fromInternalPosition(int $endAt, int $startAt = 1): Generator
    {
        $count = 1;
        while ($count < $endAt + 1) {
            $count++;

            yield $this->makeEvent([EventHeader::INTERNAL_POSITION => $startAt++]);
        }

        return $endAt;
    }

    /**
     * @param positive-int|null $internalPosition
     */
    public function withHeaders(
        DateTimeImmutable|string $eventTime = null,
        int $internalPosition = null,
        array $content = []
    ): DomainEvent {

        $headers = [];
        $internalPosition and $headers[EventHeader::INTERNAL_POSITION] = $internalPosition;
        $eventTime and $headers[Header::EVENT_TIME] = $eventTime;

        return $this->makeEvent($headers, $content);
    }

    public function timesWithHeaders(int $times, string $modifier = null, Uuid|string $eventId = null): Generator
    {
        $count = 1;
        $now = PointInTimeFactory::now();

        while ($count < $times + 1) {
            $now = $now->modify($modifier ?? '+10 seconds');

            yield self::makeEvent([
                Header::EVENT_TYPE => $this->event,
                Header::EVENT_ID => $eventId ?? UuidV4::v4()->toRfc4122(),
                Header::EVENT_TIME => $now->format(PointInTime::DATE_TIME_FORMAT),
                EventHeader::AGGREGATE_VERSION => $count,
                EventHeader::INTERNAL_POSITION => $count,
            ], ['count' => $count]);

            $count++;
        }

        return $times;
    }

    protected function makeEvent(array $headers, array $content = []): DomainEvent
    {
        /** @var DomainEvent $event */
        $event = $this->event;

        return $event::fromContent($content)->withHeaders($headers);
    }
}
