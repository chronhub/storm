<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Scheme\StreamCache;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(StreamCache::class)]
final class StreamCacheTest extends UnitTestCase
{
    public function testInstanceWitCacheSize(): void
    {
        $cache = new StreamCache(5);

        $this->assertCount(5, $cache->all());
    }

    #[DataProvider('provideInvalidCacheSize')]
    public function testExceptionRaisedWithInvalidCacheSize(int $cacheSize): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Stream cache size must be greater than 0');

        new StreamCache($cacheSize);
    }

    public function testFillCache(): void
    {
        $cache = new StreamCache(2);

        $streamName = 'foo';

        $cache->push($streamName);

        $this->assertEquals(['foo', null], $cache->all());

        $streamName = 'bar';

        $cache->push($streamName);

        $this->assertEquals(['foo', 'bar'], $cache->all());
    }

    public function testOverridePositionWhenCacheIsFull(): void
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

    public function testStreamExistsInCache(): void
    {
        $cache = new StreamCache(2);

        $firstStream = 'foo';

        $this->assertFalse($cache->has($firstStream));

        $cache->push($firstStream);

        $this->assertTrue($cache->has($firstStream));
    }

    public function testItJsonSerialize(): void
    {
        $cache = new StreamCache(2);

        $firstStream = 'foo';
        $cache->push($firstStream);

        $secondStream = 'bar';
        $cache->push($secondStream);

        $this->assertEquals(['foo', 'bar'], $cache->jsonSerialize());

        $thirdStream = 'foo_bar';
        $cache->push($thirdStream);

        $this->assertEquals(['foo_bar', 'bar'], $cache->jsonSerialize());
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
