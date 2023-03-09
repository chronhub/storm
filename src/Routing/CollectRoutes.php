<?php

declare(strict_types=1);

namespace Chronhub\Storm\Routing;

use Illuminate\Support\Collection;
use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Contracts\Routing\RouteCollection;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;

final readonly class CollectRoutes implements RouteCollection
{
    private Collection $routes;

    public function __construct(private MessageAlias $messageAlias)
    {
        $this->routes = new Collection();
    }

    public function addRoute(string $messageName): Route
    {
        $filteredRoutes = $this->routes->filter(
            fn (Route $route): bool => ($messageName === $route->getOriginalName())
        );

        if ($filteredRoutes->isNotEmpty()) {
            throw new RoutingViolation("Message name already exists: $messageName");
        }

        $route = new Route($messageName);

        $route->alias($this->messageAlias->classToAlias($messageName));

        $this->routes->push($route);

        return $route;
    }

    public function match(string $messageName): ?Route
    {
        return $this->routes->filter(
            fn (Route $route): bool => ($messageName === $route->getName())
        )->first();
    }

    public function matchOriginal(string $messageName): ?Route
    {
        return $this->routes->filter(
            fn (Route $route): bool => ($messageName === $route->getOriginalName())
        )->first();
    }

    public function getRoutes(): Collection
    {
        return clone $this->routes;
    }

    public function jsonSerialize(): array
    {
        return $this->routes->jsonSerialize();
    }
}
