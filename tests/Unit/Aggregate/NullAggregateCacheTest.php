<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Aggregate;

use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Aggregate\NullAggregateCache;
use Chronhub\Storm\Tests\Stubs\AggregateRootStub;
use Chronhub\Storm\Tests\Stubs\AnotherAggregateRootStub;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;

final class NullAggregateCacheTest extends UnitTestCase
{
    /**
     * @test
     *
     * @dataProvider provideAggregateId
     */
    public function it_always_return_null_to_get_aggregate(AggregateIdentity $aggregateId): void
    {
        $aggregateCache = new NullAggregateCache();

        $this->assertNull($aggregateCache->get($aggregateId));
    }

    /**
     * @test
     *
     * @dataProvider provideAggregateId
     */
    public function it_always_return_null_to_check_if_aggregate_exists_in_cache(AggregateIdentity $aggregateId): void
    {
        $aggregateCache = new NullAggregateCache();

        $this->assertFalse($aggregateCache->has($aggregateId));
    }

    /**
     * @test
     */
    public function it_always_return_zero_when_counting_aggregates_in_cache(): void
    {
        $aggregateCache = new NullAggregateCache();

        $aggregateCache->put(AggregateRootStub::create(V4AggregateId::create()));
        $aggregateCache->put(AnotherAggregateRootStub::create(V4AggregateId::create()));

        $this->assertEquals(0, $aggregateCache->count());
    }

    /**
     * @test
     */
    public function it_does_not_flush(): void
    {
        $aggregateCache = new NullAggregateCache();

        $cloneAggregateCache = clone $aggregateCache;

        $cloneAggregateCache->flush();

        $this->assertEquals($aggregateCache, $cloneAggregateCache);
    }

    /**
     * @test
     */
    public function it_does_not_forget(): void
    {
        $aggregateCache = new NullAggregateCache();

        $cloneAggregateCache = clone $aggregateCache;

        $cloneAggregateCache->forget(V4AggregateId::create());

        $this->assertEquals($aggregateCache, $cloneAggregateCache);
    }

    public function provideAggregateId(): Generator
    {
        yield [V4AggregateId::create()];
        yield [V4AggregateId::create()];
        yield [V4AggregateId::create()];
    }
}
