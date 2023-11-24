<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Scheme\StreamCache;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(StreamCache::class)]
final class StreamCacheTest extends UnitTestCase
{
    public function testCacheInitialization(): void
    {
        $cacheSize = 3;
        $cache = new StreamCache($cacheSize);

        $this->assertCount($cacheSize, $cache->jsonSerialize());
    }

    public function testPushStream(): void
    {
        $cacheSize = 3;
        $cache = new StreamCache($cacheSize);

        $cache->push('stream1');
        $cache->push('stream2');
        $cache->push('stream3');

        $expected = ['stream1', 'stream2', 'stream3'];
        $this->assertEquals($expected, $cache->jsonSerialize());
    }

    public function testPushStreamWithCacheRotation(): void
    {
        $cacheSize = 3;
        $cache = new StreamCache($cacheSize);

        $cache->push('stream1');
        $cache->push('stream2');
        $cache->push('stream3');
        $cache->push('stream4');

        $expected = ['stream4', 'stream2', 'stream3'];
        $this->assertEquals($expected, $cache->jsonSerialize());
    }

    public function testHasStream(): void
    {
        $cacheSize = 3;
        $cache = new StreamCache($cacheSize);

        $cache->push('stream1');

        $this->assertTrue($cache->has('stream1'));
        $this->assertFalse($cache->has('stream2'));
    }

    public function testPushDuplicateStream(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Stream stream1 is already in the cache');

        $cacheSize = 3;
        $cache = new StreamCache($cacheSize);

        $cache->push('stream1');
        $cache->push('stream1');
    }
}
