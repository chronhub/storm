<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Projector\Scheme\StreamCache;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;

#[CoversClass(StreamCache::class)]
final class StreamCacheTest extends UnitTestCase
{
    #[Test]
    public function it_can_be_constructed_with_cache_size(): void
    {
        $cache = new StreamCache(5);

        $this->assertCount(5, $cache->all());
    }

    #[DataProvider('provideInvalidCacheSize')]
    #[Test]
    public function it_raise_exception_with_cache_size_less_or_equals_than_zero(int $cacheSize): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Stream cache size must be greater than 0');

        new StreamCache($cacheSize);
    }

    #[Test]
    public function it_fill_cache_to_the_next_position(): void
    {
        $cache = new StreamCache(2);

        $streamName = 'foo';

        $cache->push($streamName);

        $this->assertEquals(['foo', null], $cache->all());
    }

    #[Test]
    public function it_override_first_position_if_cache_size_is_full(): void
    {
        $cache = new StreamCache(2);

        $firstStream = 'foo';
        $cache->push($firstStream);

        $this->assertEquals('foo', $cache->all()[0]);
        $this->assertNull($cache->all()[1]);

        $secondStream = 'bar';
        $cache->push($secondStream);

        $this->assertEquals('foo', $cache->all()[0]);
        $this->assertEquals('bar', $cache->all()[1]);

        $thirdStream = 'foo_bar';
        $cache->push($thirdStream);

        $this->assertEquals('foo_bar', $cache->all()[0]);
        $this->assertEquals('bar', $cache->all()[1]);
    }

    #[Test]
    public function it_check_if_cache_has_stream_in_any_positions(): void
    {
        $cache = new StreamCache(2);

        $firstStream = 'foo';

        $this->assertFalse($cache->has($firstStream));

        $cache->push($firstStream);

        $this->assertTrue($cache->has($firstStream));
    }

    public static function provideInvalidCacheSize(): Generator
    {
        yield [0];
        yield [-1];
    }

    public function provideInvalidPosition(): Generator
    {
        yield [-1];
        yield [2];
    }
}
