<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Routing;

use Illuminate\Support\Collection;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Routing\Exceptions\RouteNotFound;
use Chronhub\Storm\Routing\Exceptions\RouteHandlerNotSupported;

interface RouteLocator
{
    /**
     * @return Collection{string|object|callable}
     *
     * @throws RouteNotFound
     * @throws RouteHandlerNotSupported
     */
    public function route(Message $message): Collection;

    /**
     * @throws RouteNotFound
     */
    public function onQueue(Message $message): ?array;
}
