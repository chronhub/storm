<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Message\UniqueIdV4;
use Chronhub\Storm\Tests\UnitTestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;

final class UniqueIdV4Test extends UnitTestCase
{
    public function testGenerateStringUniqueId(): void
    {
        $generator = new UniqueIdV4();

        $uuid = $generator->generate();

        $this->assertTrue(Uuid::isValid($uuid));

        $instance = Uuid::fromString($uuid);

        $this->assertInstanceOf(UuidV4::class, $instance);
    }

    public function testSerializeInstance(): void
    {
        $generator = new UniqueIdV4();

        $uuid = (string) $generator;

        $this->assertTrue(Uuid::isValid($uuid));

        $instance = Uuid::fromString($uuid);

        $this->assertInstanceOf(UuidV4::class, $instance);
    }
}
