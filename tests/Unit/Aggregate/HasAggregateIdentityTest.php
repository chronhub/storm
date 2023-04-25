<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Aggregate;

use Chronhub\Storm\Aggregate\HasAggregateIdentity;
use Chronhub\Storm\Tests\Stubs\AggregateIdStub;
use Chronhub\Storm\Tests\Stubs\AnotherAggregateIdStub;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Stringable;
use Symfony\Component\Uid\Uuid;

#[CoversClass(HasAggregateIdentity::class)]
final class HasAggregateIdentityTest extends UnitTestCase
{
    private string $uid = 'f5b0c0a0-5d9b-4b3a-8c1c-8c7f66e0b3e5';

    public function testInstance(): void
    {
        $aggregateId = AggregateIdStub::fromString($this->uid);

        $this->assertInstanceOf(Uuid::class, $aggregateId->identifier);
        $this->assertInstanceOf(Stringable::class, $aggregateId);
    }

    public function testEquality(): void
    {
        $aggregateId = AggregateIdStub::fromString($this->uid);

        $this->assertTrue($aggregateId->equalsTo(AggregateIdStub::fromString($this->uid)));
        $this->assertFalse($aggregateId->equalsTo(AnotherAggregateIdStub::fromString($this->uid)));
    }

    public function testSerialize(): void
    {
        $aggregateId = AggregateIdStub::fromString($this->uid);

        $this->assertSame($this->uid, $aggregateId->toString());
        $this->assertSame($this->uid, $aggregateId->identifier->jsonSerialize());
        $this->assertSame($this->uid, (string) $aggregateId);
    }
}
