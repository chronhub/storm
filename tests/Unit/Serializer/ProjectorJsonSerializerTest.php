<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Serializer;

use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Serializer\ProjectorJsonSerializer;

final class ProjectorJsonSerializerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_encode_data(): void
    {
        $serializer = new ProjectorJsonSerializer();

        $this->assertEquals('{"foo":"bar"}', $serializer->encode(['foo' => 'bar']));
    }

    /**
     * @test
     */
    public function it_decode_data(): void
    {
        $serializer = new ProjectorJsonSerializer();

        $this->assertEquals(['foo' => 'bar'], $serializer->decode('{"foo":"bar"}'));
    }

    /**
     * @test
     */
    public function it_serialize_empty_array(): void
    {
        $serializer = new ProjectorJsonSerializer();

        $this->assertEquals('{}', $serializer->encode([]));
    }

    /**
     * @test
     */
    public function it_deserialize_string_to_empty_array(): void
    {
        $serializer = new ProjectorJsonSerializer();

        $this->assertEquals([], $serializer->decode('{}'));
    }
}
