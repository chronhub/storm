<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Factory;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamIterator;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;

use function array_keys;
use function array_values;

class MergeStreamIteratorFactory
{
    /**
     * @return array<string,Generator>
     */
    public static function getData(): array
    {
        $factory = StreamEventsFactory::withEvent(SomeEvent::class);

        return [
            'stream1' => $factory::fromArray([
                $factory->withHeaders('2023-05-10T10:16:19.000000', 1),
                $factory->withHeaders('2023-05-10T10:17:19.000000', 4),
                $factory->withHeaders('2023-05-10T10:24:19.000000', 6),
            ]),
            'stream2' => $factory::fromArray([
                $factory->withHeaders('2023-05-10T10:15:19.000000', 5),
                $factory->withHeaders('2023-05-10T10:20:19.000000', 7),
                $factory->withHeaders('2023-05-10T10:22:19.000000', 2),
            ]),
            'stream3' => $factory::fromArray([
                $factory->withHeaders('2023-05-10T10:18:19.000000', 3),
                $factory->withHeaders('2023-05-10T10:19:19.000000', 8),
                $factory->withHeaders('2023-05-10T10:23:19.000000', 9),
            ]),
        ];
    }

    public static function expectedIteratorOrder(): array
    {
        return [
            'stream2', 'stream1', 'stream1',
            'stream3', 'stream3', 'stream2',
            'stream2', 'stream3', 'stream1',
        ];
    }

    public static function expectedIteratorPosition(): array
    {
        return [5, 1, 4, 3, 8, 7, 2, 9, 6];
    }

    public static function getIterator(SystemClock|MockObject $clock = null): MergeStreamIterator
    {
        $streams = self::getStreams();

        if ($clock === null) {
            $clock = new PointInTime();
        }

        return new MergeStreamIterator($clock, array_keys($streams), ...array_values($streams));
    }

    /**
     * @return array<string,StreamIterator>
     */
    protected static function getStreams(): array
    {
        $data = self::getData();

        $streams = [];

        foreach ($data as $streamName => $streamEvents) {
            $streams[$streamName] = new StreamIterator($streamEvents);
        }

        return $streams;
    }
}
