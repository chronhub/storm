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
     * Return array of message handler if route matched
     *
     * @param  Message  $message
     * @return Collection
     *
     * @throws RouteNotFound
     * @throws RouteHandlerNotSupported
     */
    public function route(Message $message): Collection;

    /**
     * Return route queue options if set
     *
     * @param  Message  $message
     * @return array|null
     *
     * @throws RouteNotFound
     */
    public function onQueue(Message $message): ?array;
}
