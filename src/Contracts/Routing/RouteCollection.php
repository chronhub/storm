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
     * @return Route
     */
    public function addRoute(string $messageName): Route;

    /**
     * Add route instance
     *
     * @param  Route  $route
     * @return RouteCollection
     */
    public function addRouteInstance(Route $route): static;

    /**
     * Find route by message name
     *
     * @param  string|class-string  $messageName
     * @return Route|null
     */
    public function match(string $messageName): ?Route;

    /**
     * Find route by original message name
     *
     * @param  class-string  $messageName
     * @return Route|null
     */
    public function matchOriginal(string $messageName): ?Route;

    /**
     * Return route collection
     *
     * @return Collection
     */
    public function getRoutes(): Collection;
}
