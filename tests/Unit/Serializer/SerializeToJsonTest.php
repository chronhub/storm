<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Serializer;

use Chronhub\Storm\Serializer\SerializeToJson;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

use function is_string;

#[CoversClass(SerializeToJson::class)]
final class SerializeToJsonTest extends UnitTestCase
{
    public function testEncodeData(): void
    {
        $serializer = new SerializeToJson();

        $this->assertEquals('{"foo":"bar"}', $serializer->encode(['foo' => 'bar']));
    }

    public function testDecodeData(): void
    {
        $serializer = new SerializeToJson();

        $this->assertEquals(['foo' => 'bar'], $serializer->decode('{"foo":"bar"}'));
    }

    public function testSerializeEmptyArray(): void
    {
        $serializer = new SerializeToJson();

        $this->assertEquals('[]', $serializer->encode([]));
    }

    public function testDeserializeEmptyArrayToEmptyArray(): void
    {
        $serializer = new SerializeToJson();

        $this->assertEquals([], $serializer->decode('[]'));
    }

    public function testDeserializeBigIntegerToString(): void
    {
        $serializer = new SerializeToJson();

        $integer = $serializer->decode('{"foo":9999999999999999999999}');

        $this->assertTrue(is_string($integer['foo']));
    }

    public function testGetInnerJsonEncoder(): void
    {
        $serializer = new SerializeToJson();

        $this->assertEquals(JsonEncoder::class, $serializer->getEncoder()::class);
        $this->assertEquals($serializer->json::class, $serializer->getEncoder()::class);
        $this->assertSame($serializer->json, $serializer->getEncoder());
    }
}
