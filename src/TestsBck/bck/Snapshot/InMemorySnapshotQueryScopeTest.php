<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Snapshot;

use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Snapshot\InMemorySnapshotQueryScope;
use Chronhub\Storm\Snapshot\MatchAggregateBetweenIncludedVersion;
use Chronhub\Storm\Snapshot\MatchAggregateGreaterThanVersion;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tests\Util\ReflectionProperty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(InMemorySnapshotQueryScope::class)]
final class InMemorySnapshotQueryScopeTest extends UnitTestCase
{
    private AggregateIdentity|MockObject $aggregateId;

    protected function setUp(): void
    {
       $this->aggregateId = $this->createMock(AggregateIdentity::class);
    }

    public function testMatchAggregateGreaterThanVersionInstance(): void
    {
        $queryScope = new InMemorySnapshotQueryScope();

        $filter = $queryScope->matchAggregateGreaterThanVersion($this->aggregateId, 'user', 1);

        $this->assertSame(MatchAggregateGreaterThanVersion::class, $filter::class);
        $this->assertSame($this->aggregateId, ReflectionProperty::getProperty($filter, 'aggregateId'));
        $this->assertSame('user', ReflectionProperty::getProperty($filter, 'aggregateType'));
        $this->assertSame(1, ReflectionProperty::getProperty($filter, 'aggregateVersion'));
    }

    public function testMatchAggregateBetweenIncludedVersionInstance(): void
    {
        $queryScope = new InMemorySnapshotQueryScope();

        $filter = $queryScope->matchAggregateBetweenIncludedVersion($this->aggregateId, 1, 250);

        $this->assertSame(MatchAggregateBetweenIncludedVersion::class, $filter::class);
        $this->assertSame($this->aggregateId, ReflectionProperty::getProperty($filter, 'aggregateId'));
        $this->assertSame(1, ReflectionProperty::getProperty($filter, 'fromVersion'));
        $this->assertSame(250, ReflectionProperty::getProperty($filter, 'toVersion'));
    }
}
