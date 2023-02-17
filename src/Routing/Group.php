<?php

declare(strict_types=1);

namespace Chronhub\Storm\Routing;

use JsonSerializable;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Producer\ProducerStrategy;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Contracts\Routing\RouteCollection;
use Chronhub\Storm\Contracts\Message\MessageDecorator;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use function is_a;
use function array_merge;

abstract class Group implements JsonSerializable
{
    /**
     * Reporter service id
     *
     * Use to register the reporter in ioc
     */
    private ?string $reporterServiceId = null;

    /**
     * Reporter concrete class name
     *
     * will be used as default binding if reporter service id is not set
     */
    private ?string $reporterConcrete = null;

    /**
     * Tracker service id
     */
    private ?string $messageTrackerId = null;

    /**
     * Message handler method name
     *
     * default null for __invoke magic method
     */
    private ?string $messageHandlerMethodName = null;

    /**
     * Message producer strategy key
     */
    private ?ProducerStrategy $producerStrategy = null;

    /**
     * Message producer service id
     */
    private ?string $producerServiceId = null;

    /**
     * Group queue
     */
    private ?array $queue = null;

    /**
     * Append Message Decorators
     *
     * @var array<string|MessageDecorator>
     */
    private array $messageDecorators = [];

    /**
     * Append message subscribers
     *
     * @var array<string|MessageSubscriber>
     */
    private array $messageSubscribers = [];

    public function __construct(public readonly string $name,
                                public readonly RouteCollection $routes)
    {
    }

    public function reporterServiceId(): ?string
    {
        return $this->reporterServiceId;
    }

    public function withReporterServiceId(string $reporterServiceId): self
    {
        $this->reporterServiceId = $reporterServiceId;

        return $this;
    }

    public function reporterConcrete(): ?string
    {
        return $this->reporterConcrete;
    }

    public function withReporterConcreteClass(string $reporterConcrete): self
    {
        if (! is_a($reporterConcrete, Reporter::class, true)) {
            throw new RoutingViolation("Reporter concrete class $reporterConcrete must be an instance of ".Reporter::class);
        }

        $this->reporterConcrete = $reporterConcrete;

        return $this;
    }

    public function trackerId(): ?string
    {
        return $this->messageTrackerId;
    }

    public function withTrackerId(string $trackerId): self
    {
        $this->messageTrackerId = $trackerId;

        return $this;
    }

    public function messageHandlerMethodName(): ?string
    {
        return $this->messageHandlerMethodName;
    }

    public function withMessageHandlerMethodName(string $messageHandlerMethodName): self
    {
        $this->messageHandlerMethodName = $messageHandlerMethodName;

        return $this;
    }

    public function messageDecorators(): array
    {
        return $this->messageDecorators;
    }

    public function withMessageDecorators(string|MessageDecorator ...$messageDecorators): self
    {
        $this->messageDecorators = array_merge($this->messageDecorators, $messageDecorators);

        return $this;
    }

    public function messageSubscribers(): array
    {
        return $this->messageSubscribers;
    }

    public function withMessageSubscribers(string|MessageSubscriber ...$messageSubscribers): self
    {
        $this->messageSubscribers = array_merge($this->messageSubscribers, $messageSubscribers);

        return $this;
    }

    public function producerStrategy(): ProducerStrategy
    {
        if ($this->producerStrategy === null) {
            throw new RoutingViolation('Producer strategy can not be null');
        }

        return $this->producerStrategy;
    }

    public function withProducerStrategy(string $producerStrategy): self
    {
        $strategy = ProducerStrategy::tryFrom($producerStrategy);

        if ($strategy === null) {
            throw new RoutingViolation('Invalid message producer key: unknown_strategy');
        }

        $this->producerStrategy = $strategy;

        return $this;
    }

    public function producerServiceId(): ?string
    {
        return $this->producerServiceId;
    }

    public function withProducerServiceId(string $producerServiceId): self
    {
        $this->producerServiceId = $producerServiceId;

        return $this;
    }

    public function queue(): ?array
    {
        return $this->queue;
    }

    public function withQueue(?array $queue): self
    {
        $this->queue = $queue;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            $this->getType()->value => [
                $this->name => [
                    'group' => [
                        'service_id' => $this->producerServiceId(),
                        'concrete' => $this->reporterConcrete(),
                        'tracker_id' => $this->trackerId(),
                        'handler_method_name' => $this->messageHandlerMethodName(),
                        'message_decorators' => $this->messageDecorators(),
                        'message_subscribers' => $this->messageSubscribers(),
                        'producer_strategy' => $this->producerStrategy()->value,
                        'producer_service_id' => $this->producerServiceId(),
                        'queue' => $this->queue(),
                    ],
                    'routes' => $this->routes->jsonSerialize(),
                ],
            ],
        ];
    }

    abstract public function getType(): DomainType;
}
