<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler\InMemory;

use Chronhub\Storm\Chronicler\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Chronicler\ProvideChroniclerFactory;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\ChroniclerFactory;
use Chronhub\Storm\Contracts\Chronicler\EventableChronicler;
use Chronhub\Storm\Contracts\Chronicler\InMemoryChronicler;
use Chronhub\Storm\Contracts\Chronicler\TransactionalEventableChronicler;
use Chronhub\Storm\Contracts\Chronicler\TransactionalInMemoryChronicler as Transactional;
use Chronhub\Storm\Contracts\Stream\StreamCategory;
use Closure;

final class InMemoryChroniclerFactory implements ChroniclerFactory
{
    use ProvideChroniclerFactory;

    public function __construct(Closure $app)
    {
        $this->container = $app();
    }

    public function createEventStore(string $name, array $config): Chronicler
    {
        $chronicler = $this->resolve($name, $config);

        if ($chronicler instanceof EventableChronicler) {
            $this->attachStreamSubscribers($chronicler, $config['tracking']['subscribers'] ?? []);
        }

        return $chronicler;
    }

    private function resolve(string $name, array $config): Chronicler
    {
        return match ($name) {
            'standalone' => $this->createStandaloneDriver(),
            'transactional' => $this->createTransactionalDriver(),
            'eventable' => $this->createEventableDriver($config),
            'transactional_eventable' => $this->createTransactionalEventableDriver($config),
            default => throw new InvalidArgumentException("In memory chronicler provider $name is not defined")
        };
    }

    private function createStandaloneDriver(): InMemoryChronicler
    {
        return new StandaloneInMemoryChronicler(
            new InMemoryEventStream(),
            $this->container->get(StreamCategory::class)
        );
    }

    private function createTransactionalDriver(): Transactional
    {
        return new TransactionalInMemoryChronicler(
            new InMemoryEventStream(),
            $this->container->get(StreamCategory::class)
        );
    }

    private function createEventableDriver(array $config): EventableChronicler
    {
        return $this->decorateChronicler(
            $this->createStandaloneDriver(),
            $this->resolveStreamTracker($config)
        );
    }

    private function createTransactionalEventableDriver(array $config): TransactionalEventableChronicler
    {
        return $this->decorateChronicler(
            $this->createTransactionalDriver(),
            $this->resolveStreamTracker($config)
        );
    }
}
