<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Routing;

use JsonSerializable;
use Chronhub\Storm\Routing\Route;
use Illuminate\Support\Collection;

interface RouteCollection extends JsonSerializable
{
    /**
     * @param  class-string  $messageName
     */
    public function addRoute(string $messageName): Route;

    public function addRouteInstance(Route $route): static;

    /**
     * @param  string|class-string  $messageName
     */
    public function match(string $messageName): ?Route;

    /**
     * @param  class-string  $messageName
     */
    public function matchOriginal(string $messageName): ?Route;

    /**
     * @return Collection<Route>
     */
    public function getRoutes(): Collection;
}
