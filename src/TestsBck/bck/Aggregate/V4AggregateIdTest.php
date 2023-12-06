<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Aggregate;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Uid\Uuid;

#[CoversClass(V4AggregateId::class)]
final class V4AggregateIdTest extends UnitTestCase
{
    private AggregateIdentity|V4AggregateId $aggregateId;

    protected function setUp(): void
    {
        $this->aggregateId = V4AggregateId::create();
    }

    public function testInstance(): void
    {
        $this->assertInstanceOf(AggregateIdentity::class, $this->aggregateId);
        $this->assertInstanceOf(V4AggregateId::class, $this->aggregateId);

        $this->assertInstanceOf(Uuid::class, $this->aggregateId->identifier);
    }

    public function testItCanBeCreatedFromString(): void
    {
        $aggregateId = $this->aggregateId;

        $fromString = V4AggregateId::fromString((string) $aggregateId);

        $this->assertEquals($aggregateId, $fromString);
        $this->assertNotSame($aggregateId, $fromString);
    }

    public function testCheckEquality(): void
    {
        $aggregateId = $this->aggregateId;
        $anotherAggregateId = V4AggregateId::create();

        $this->assertNotSame($aggregateId, $anotherAggregateId);
        $this->assertFalse($aggregateId->equalsTo($anotherAggregateId));
        $this->assertFalse($anotherAggregateId->equalsTo($aggregateId));
        $this->assertTrue($aggregateId->equalsTo($aggregateId));
        $this->assertTrue($anotherAggregateId->equalsTo($anotherAggregateId));
    }

    public function testInstanceCanBeSerialized(): void
    {
        $aggregateId = V4AggregateId::fromString('99533317-44b3-48cc-9148-f385eddb73e9');

        $this->assertEquals('99533317-44b3-48cc-9148-f385eddb73e9', $aggregateId->toString());
        $this->assertEquals('99533317-44b3-48cc-9148-f385eddb73e9', (string) $aggregateId);
    }
}
