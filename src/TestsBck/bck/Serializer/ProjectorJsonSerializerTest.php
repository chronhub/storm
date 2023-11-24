<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Serializer;

use Chronhub\Storm\Serializer\ProjectorJsonSerializer;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProjectorJsonSerializer::class)]
final class ProjectorJsonSerializerTest extends UnitTestCase
{
    public function testEncodeData(): void
    {
        $serializer = new ProjectorJsonSerializer();

        $this->assertEquals('{"foo":"bar"}', $serializer->encode(['foo' => 'bar']));
    }

    public function testDecodeData(): void
    {
        $serializer = new ProjectorJsonSerializer();

        $this->assertEquals(['foo' => 'bar'], $serializer->decode('{"foo":"bar"}'));
    }

    public function testSerializeEmptyArray(): void
    {
        $serializer = new ProjectorJsonSerializer();

        $this->assertEquals('{}', $serializer->encode([]));
    }

    public function testDeserializeStringToEmptyArray(): void
    {
        $serializer = new ProjectorJsonSerializer();

        $this->assertEquals([], $serializer->decode('{}'));
    }

    public function testDeserializeBigIntegerToString(): void
    {
        $serializer = new ProjectorJsonSerializer();

        $data = $serializer->encode(['foo' => 99999999999999999999999999]);

        $this->assertEquals(['foo' => '99999999999999999999999999'], $serializer->decode($data));
    }
}
