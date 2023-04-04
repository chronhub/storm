<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Routing;

use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Routing\Exceptions\RouteHandlerNotSupported;
use Chronhub\Storm\Routing\Exceptions\RouteNotFound;
use Illuminate\Support\Collection;

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
