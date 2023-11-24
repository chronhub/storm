<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Snapshot;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Snapshot\Base64EncodeSerializer;
use Chronhub\Storm\Tests\Stubs\AggregateRootStub;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use function base64_decode;
use function base64_encode;
use function serialize;
use function unserialize;

#[CoversClass(Base64EncodeSerializer::class)]
final class Base64EncodeSerializerTest extends UnitTestCase
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

        $serializer = new Base64EncodeSerializer();

        $serialized = $serializer->serialize($aggregate);
        $encoded = base64_encode($serialized);

        $this->assertEquals(base64_encode(serialize($aggregate)), $serialized);
        $this->assertEquals(unserialize(base64_decode($serialized, true)), $aggregate);
        $this->assertEquals($aggregate, $serializer->deserialize(base64_decode($encoded, true)));
    }
}
