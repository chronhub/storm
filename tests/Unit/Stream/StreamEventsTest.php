<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Stream;

use ArrayObject;
use Chronhub\Storm\Stream\StreamEvents;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Countable;
use Generator;
use IteratorAggregate;
use PHPUnit\Framework\Attributes\DataProvider;
use Traversable;

final class StreamEventsTest extends UnitTestCase
{
    #[DataProvider('provideIterable')]
    public function testInstance(iterable $events): void
    {
        $events = new StreamEvents($events);

        $this->assertInstanceOf(IteratorAggregate::class, $events);
        $this->assertInstanceOf(Countable::class, $events);

        $this->assertCount(1, $events);
        $this->assertIsIterable($events);

        $expectedEvent = null;
        foreach ($events as $event) {
            $expectedEvent = $event;
        }

        $this->assertEquals(SomeEvent::fromContent(['foo' => 'bar']), $expectedEvent);
    }

    public static function provideIterable(): Generator
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);

        yield [[$event]];

        yield [new ArrayObject([$event])];

        yield [new class([$event]) implements IteratorAggregate
        {
            public function __construct(public array $events)
            {
            }

            public function getIterator(): Traversable
            {
                yield from $this->events;
            }
        }];

        yield [self::generateEvent($event)];
    }

    private static function generateEvent(SomeEvent $event): Generator
    {
        yield $event;

        return 1;
    }
}
