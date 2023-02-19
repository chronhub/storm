<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Serializer;

use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Serializer\JsonSerializer;

final class JsonSerializerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_assert_force_object_on_encode(): void
    {
        $json = new JsonSerializer();

        $result = $json->getEncoder()->encode([], 'json', $json::CONTEXT);

        $this->assertEquals('{}', $result);
    }
}
