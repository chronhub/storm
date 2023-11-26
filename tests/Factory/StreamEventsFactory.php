<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Factory;

use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Generator;

class StreamEventsFactory
{
    public static function generate(array $payload, array $headers = [], $count = 1): Generator
    {
        $num = $count;
        while ($num > 0) {
            $num--;

            yield SomeEvent::fromContent($payload)->withHeaders($headers);
        }

        return $count;
    }
}
