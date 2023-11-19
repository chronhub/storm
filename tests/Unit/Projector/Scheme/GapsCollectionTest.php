<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Projector\Scheme\GapsCollection;
use Chronhub\Storm\Tests\UnitTestCase;

class GapsCollectionTest extends UnitTestCase
{
    private GapsCollection $gapsCollection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gapsCollection = new GapsCollection();
    }

    /**
     * @test
     */
    public function testInstance(): void
    {
        $this->assertEmpty($this->gapsCollection->all());
    }

    /**
     * @test
     */
    public function testAllReturnCloneCollection(): void
    {
        $this->assertEmpty($this->gapsCollection->all());

        $this->gapsCollection->put(1, true);

        $c1 = $this->gapsCollection->all();
        $c2 = $this->gapsCollection->all();

        $this->assertEquals($c1, $c2);
        $this->assertNotSame($c1, $c2);
    }

    public function testPut(): void
    {
        $this->gapsCollection->put(1, true);
        $this->gapsCollection->put(2, false);

        $gaps = $this->gapsCollection->all();

        $this->assertCount(2, $gaps);
        $this->assertTrue($gaps->get(1));
        $this->assertFalse($gaps->get(2));
    }

    public function testRemove(): void
    {
        $this->gapsCollection->put(1, true);
        $this->gapsCollection->put(5, false);

        $this->assertCount(2, $this->gapsCollection->all());

        $this->gapsCollection->remove(1);

        $this->assertCount(1, $this->gapsCollection->all());
    }

    public function testMerge(): void
    {
        $this->gapsCollection->put(1, true);
        $this->gapsCollection->merge([2, 3, 4]);

        $gaps = $this->gapsCollection->all();

        $this->assertCount(4, $gaps);
        $this->assertTrue($gaps->get(1));
        $this->assertTrue($gaps->get(2));
        $this->assertTrue($gaps->get(3));
        $this->assertTrue($gaps->get(4));
    }

    public function testMergeAndOverrideExistingGap(): void
    {
        $this->gapsCollection->put(1, true);
        $this->gapsCollection->put(2, false);

        $this->gapsCollection->merge([2, 3, 4]);

        $gaps = $this->gapsCollection->all();

        $this->assertCount(4, $gaps);

        $this->assertTrue($gaps->get(2));
    }

    public function testFilterConfirmedGaps(): void
    {
        $this->gapsCollection->put(1, true);
        $this->gapsCollection->put(2, false);
        $this->gapsCollection->put(3, true);

        $filteredGaps = $this->gapsCollection->filterConfirmedGaps();

        $this->assertCount(2, $filteredGaps);
        $this->assertEquals([1, 3], $filteredGaps);
    }
}
