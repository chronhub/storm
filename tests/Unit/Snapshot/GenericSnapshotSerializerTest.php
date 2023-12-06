<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Snapshot;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Snapshot\GenericSnapshotSerializer;
use Chronhub\Storm\Tests\Stubs\AggregateRootStub;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use function serialize;
use function unserialize;

#[CoversClass(GenericSnapshotSerializer::class)]
class GenericSnapshotSerializerTest extends UnitTestCase
{
    public function testSerialize(): void
    {
        $aggregateId = V4AggregateId::create();

        $events = [
            SomeEvent::fromContent(['foo' => 'bar'])->withHeader(EventHeader::AGGREGATE_ID, $aggregateId),
            SomeEvent::fromContent(['foo' => 'baz'])->withHeader(EventHeader::AGGREGATE_ID, $aggregateId),
        ];

        $aggregate = AggregateRootStub::create($aggregateId, ...$events);
        $aggregate->releaseEvents();

        $this->assertEquals(2, $aggregate->version());

        $serializer = new GenericSnapshotSerializer();

        $serialized = $serializer->serialize($aggregate);

        $this->assertEquals(serialize($aggregate), $serialized);
        $this->assertEquals(unserialize($serialized), $aggregate);
        $this->assertEquals($aggregate, $serializer->deserialize($serialized));
    }
}
