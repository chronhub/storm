<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Routing;

use JsonSerializable;
use Chronhub\Storm\Routing\Route;
use Illuminate\Support\Collection;

interface RouteCollection extends JsonSerializable
{
    /**
     * Add route with given concrete name
     *
     * @param  class-string  $messageName
     */
    public function addRoute(string $messageName): Route;

    /**
     * Add route instance
     */
    public function addRouteInstance(Route $route): static;

    /**
     * Find route by message name
     *
     * @param  string|class-string  $messageName
     */
    public function match(string $messageName): ?Route;

    /**
     * Find route by original message name
     *
     * @param  class-string  $messageName
     */
    public function matchOriginal(string $messageName): ?Route;

    /**
     * Return route collection
     */
    public function getRoutes(): Collection;
}
