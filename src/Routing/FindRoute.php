<?php

declare(strict_types=1);

namespace Chronhub\Storm\Routing;

use Illuminate\Support\Collection;
use Chronhub\Storm\Message\Message;
use Psr\Container\ContainerInterface;
use Chronhub\Storm\Contracts\Message\Header;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Contracts\Routing\RouteLocator;
use Chronhub\Storm\Routing\Exceptions\RouteNotFound;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use Chronhub\Storm\Routing\Exceptions\RouteHandlerNotSupported;
use function count;
use function is_string;
use function is_callable;
use function method_exists;

final readonly class FindRoute implements RouteLocator
{
    public function __construct(private Group $group,
                                private MessageAlias $messageAlias,
                                private ContainerInterface $container)
    {
    }

    public function route(Message $message): Collection
    {
        $messageName = $this->determineMessageName($message);

        $messageHandlers = $this
            ->determineMessageHandler($messageName)
            ->map(fn ($messageHandler): callable => $this->toCallable($messageHandler, $messageName));

        // wip
        if ($this->group instanceof CommandGroup || $this->group instanceof QueryGroup) {
            if (count($messageHandlers) !== 1) {
                throw new RoutingViolation("Group {$this->group->getType()->value} required one handler only for message name $messageName");
            }
        }

        return $messageHandlers;
    }

    public function onQueue(Message $message): ?array
    {
        $messageName = $this->determineMessageName($message);

        $route = $this->group->routes->match($messageName);

        if (! $route instanceof Route) {
            throw RouteNotFound::withMessageName($messageName);
        }

        return $route->getQueue();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function toCallable(callable|object|string $consumer, string $messageName): callable
    {
        if (is_string($consumer)) {
            $consumer = $this->container->get($consumer);
        }

        if (is_callable($consumer)) {
            return $consumer;
        }

        $callableMethod = $this->group->messageHandlerMethodName();

        if (is_string($callableMethod) && method_exists($consumer, $callableMethod)) {
            return $consumer->$callableMethod(...);
        }

        throw RouteHandlerNotSupported::withMessageName($messageName);
    }

    private function determineMessageHandler(string $messageName): Collection
    {
        $route = $this->group->routes->match($messageName);

        if ($route instanceof Route) {
            return new Collection($route->getHandlers());
        }

        throw RouteNotFound::withMessageName($messageName);
    }

    private function determineMessageName(Message $message): string
    {
        return $this->messageAlias->classToAlias($message->header(Header::EVENT_TYPE));
    }
}
