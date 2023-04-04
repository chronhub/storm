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
use Illuminate\Support\Str;
use function method_exists;
use function sprintf;
use function ucfirst;

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
        // fixMe illuminate support is not part of composer dep
        // either set it or make the fn
        $driverMethod = 'create'.ucfirst(Str::camel($name.'Driver'));

        /**
         * @covers createStandaloneDriver
         * @covers createTransactionalDriver
         * @covers createEventableDriver
         * @covers createTransactionalEventableDriver
         */
        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        }

        throw new InvalidArgumentException(sprintf('In memory chronicler provider %s is not defined', $name));
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
