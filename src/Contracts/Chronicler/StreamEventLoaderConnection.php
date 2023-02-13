<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

use stdClass;
use Generator;
use Chronhub\Storm\Stream\StreamName;
use Illuminate\Database\Query\Builder;
use Chronhub\Storm\Reporter\DomainEvent;

interface StreamEventLoaderConnection extends StreamEventLoader
{
    /**
     * @param  Builder  $builder
     * @param  StreamName  $streamName
     * @return Generator<DomainEvent|stdClass|array>
     */
    public function query(Builder $builder, StreamName $streamName): Generator;
}
