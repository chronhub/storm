<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Aggregate;

use Chronhub\Storm\Aggregate\AggregateEventReleaser;
use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\MessageDecorator;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Message\NoOpMessageDecorator;
use Chronhub\Storm\Tests\Stubs\AggregateRootStub;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

use function iterator_to_array;

final class AggregateEventReleaserTest extends UnitTestCase
{
    #[DataProvider('provideMessageDecorator')]
    public function testReleaseAndDecorateEventHeaders(MessageDecorator $messageDecorator): void
    {
        $aggregateId = V4AggregateId::create();
        $events = iterator_to_array($this->provideFourDomainEvents());

        $aggregateRoot = AggregateRootStub::create($aggregateId, ...$events);

        $this->assertEquals(4, $aggregateRoot->version());

        $aggregateReleaser = new AggregateEventReleaser($messageDecorator);

        $events = $aggregateReleaser->releaseEvents($aggregateRoot);

        $i = 0;
        while ($i < 4) {
            $this->assertEquals($aggregateId->toString(), $events[$i]->header(EventHeader::AGGREGATE_ID));
            $this->assertEquals(V4AggregateId::class, $events[$i]->header(EventHeader::AGGREGATE_ID_TYPE));
            $this->assertEquals(AggregateRootStub::class, $events[$i]->header(EventHeader::AGGREGATE_TYPE));
            $this->assertEquals($i + 1, $events[$i]->header(EventHeader::AGGREGATE_VERSION));

            if (! $messageDecorator instanceof NoOpMessageDecorator) {
                $this->assertEquals('bar', $events[$i]->header('foo'));
            } else {
                $this->assertNull($events[$i]->header('foo'));
            }

            $i++;
        }
    }

    public function testReturnEmptyArrayWhenNoEventToRelease(): void
    {
        $messageDecorator = $this->createMock(MessageDecorator::class);
        $messageDecorator->expects($this->never())->method('decorate');

        $aggregateId = V4AggregateId::create();

        $aggregateRoot = AggregateRootStub::create($aggregateId);

        $this->assertEquals(0, $aggregateRoot->version());

        $aggregateReleaser = new AggregateEventReleaser($messageDecorator);

        $events = $aggregateReleaser->releaseEvents($aggregateRoot);

        $this->assertEmpty($events);
    }

    public static function provideMessageDecorator(): Generator
    {
        yield [new NoOpMessageDecorator()];
        yield [new class() implements MessageDecorator
        {
            public function decorate(Message $message): Message
            {
                return $message->withHeader('foo', 'bar');
            }
        }];
    }

    private function provideFourDomainEvents(): Generator
    {
        yield from [
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
        ];

        return 4;
    }
}
