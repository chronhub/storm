<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;
use Chronhub\Storm\Message\UniqueIdV4;
use Chronhub\Storm\Tests\UnitTestCase;

final class UniqueIdV4Test extends UnitTestCase
{
    /**
     * @test
     */
    public function it_can_be_generated(): void
    {
        $generator = new UniqueIdV4();

        $uuid = $generator->generate();

        $this->assertTrue(Uuid::isValid($uuid));

        $instance = Uuid::fromString($uuid);

        $this->assertInstanceOf(UuidV4::class, $instance);
    }

    /**
     * @test
     */
    public function it_can_be_serialized(): void
    {
        $generator = new UniqueIdV4();

        $uuid = (string) $generator;

        $this->assertTrue(Uuid::isValid($uuid));

        $instance = Uuid::fromString($uuid);

        $this->assertInstanceOf(UuidV4::class, $instance);
    }
}
