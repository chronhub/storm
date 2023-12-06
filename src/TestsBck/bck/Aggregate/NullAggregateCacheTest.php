<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Aggregate;

use Chronhub\Storm\Aggregate\NullAggregateCache;
use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Tests\Stubs\AggregateRootStub;
use Chronhub\Storm\Tests\Stubs\AnotherAggregateRootStub;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(NullAggregateCache::class)]
final class NullAggregateCacheTest extends UnitTestCase
{
    private NullAggregateCache $aggregateCache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aggregateCache = new NullAggregateCache();
    }

    #[DataProvider('provideAggregateId')]
    public function testAlwaysReturnNull(AggregateIdentity $aggregateId): void
    {
        $this->assertNull($this->aggregateCache->get($aggregateId));
    }

    #[DataProvider('provideAggregateId')]
    public function testReturnAlwaysFalseIfAggregateInCache(AggregateIdentity $aggregateId): void
    {
        $this->assertFalse($this->aggregateCache->has($aggregateId));
    }

    public function testReturnZeroCountAggregatesInCache(): void
    {
        $this->aggregateCache->put(AggregateRootStub::create(V4AggregateId::create()));
        $this->aggregateCache->put(AnotherAggregateRootStub::create(V4AggregateId::create()));

        $this->assertEquals(0, $this->aggregateCache->count());
    }

    public function testFlushVoid(): void
    {
        $aggregateCache = $this->aggregateCache;

        $cloneAggregateCache = clone $aggregateCache;

        $cloneAggregateCache->flush();

        $this->assertEquals($aggregateCache, $cloneAggregateCache);
    }

    public function testForgetVoid(): void
    {
        $aggregateCache = $this->aggregateCache;

        $cloneAggregateCache = clone $aggregateCache;

        $cloneAggregateCache->forget(V4AggregateId::create());

        $this->assertEquals($aggregateCache, $cloneAggregateCache);
    }

    public static function provideAggregateId(): Generator
    {
        yield [V4AggregateId::create()];
        yield [V4AggregateId::create()];
        yield [V4AggregateId::create()];
    }
}
