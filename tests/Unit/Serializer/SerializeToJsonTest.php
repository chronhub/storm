<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Serializer;

use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Serializer\SerializeToJson;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

final class SerializeToJsonTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_encode_data(): void
    {
        $serializer = new SerializeToJson();

        $this->assertEquals('{"foo":"bar"}', $serializer->encode(['foo' => 'bar']));
    }

    /**
     * @test
     */
    public function it_decode_data(): void
    {
        $serializer = new SerializeToJson();

        $this->assertEquals(['foo' => 'bar'], $serializer->decode('{"foo":"bar"}'));
    }

    /**
     * @test
     */
    public function it_serialize_empty_array_and_does_not_force_object_array(): void
    {
        $serializer = new SerializeToJson();

        $this->assertEquals('[]', $serializer->encode([]));
    }

    /**
     * @test
     */
    public function it_deserialize_string_as_array_to_empty_array(): void
    {
        $serializer = new SerializeToJson();

        $this->assertEquals([], $serializer->decode('[]'));
    }

    /**
     * @test
     */
    public function it_access_inner_json_encoder(): void
    {
        $serializer = new SerializeToJson();

        $this->assertEquals(JsonEncoder::class, $serializer->getEncoder()::class);
        $this->assertEquals($serializer->json::class, $serializer->getEncoder()::class);
        $this->assertSame($serializer->json, $serializer->getEncoder());
    }
}
