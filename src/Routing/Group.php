<?php

declare(strict_types=1);

namespace Chronhub\Storm\Routing;

use Chronhub\Storm\Contracts\Message\MessageDecorator;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Contracts\Routing\RouteCollection;
use Chronhub\Storm\Contracts\Routing\RoutingRule;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;
use Chronhub\Storm\Producer\ProducerStrategy;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use JsonSerializable;
use function array_merge;
use function is_a;
use function sprintf;

abstract class Group implements JsonSerializable
{
    /**
     * Reporter service id
     *
     * Use to register the reporter in ioc
     */
    private ?string $reporterId = null;

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
    private ?string $handlerMethod = null;

    /**
     * Message producer strategy key
     */
    private ?ProducerStrategy $strategy = null;

    /**
     * Message producer service id
     */
    private ?string $producerId = null;

    /**
     * Group queue
     */
    private ?array $queue = null;

    /**
     * Add Message Decorators
     *
     * @var array<string|MessageDecorator>
     */
    private array $messageDecorators = [];

    /**
     * Add Message Subscribers
     *
     * @var array<string|MessageSubscriber>
     */
    private array $messageSubscribers = [];

    /**
     * @var array <RoutingRule>
     */
    private array $rules = [];

    public function __construct(
        public readonly string $name,
        public readonly RouteCollection $routes
    ) {
    }

    public function reporterId(): ?string
    {
        return $this->reporterId;
    }

    public function withReporterId(string $reporterId): self
    {
        $this->reporterId = $reporterId;

        return $this;
    }

    public function reporterConcrete(): ?string
    {
        return $this->reporterConcrete;
    }

    public function withReporterConcrete(string $reporterConcrete): self
    {
        if (! is_a($reporterConcrete, Reporter::class, true)) {
            throw new RoutingViolation(
                sprintf('Reporter concrete class %s must be an instance of %s',
                    $reporterConcrete, Reporter::class
                )
            );
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

    public function handlerMethod(): ?string
    {
        return $this->handlerMethod;
    }

    public function withHandlerMethod(string $handlerMethod): self
    {
        $this->handlerMethod = $handlerMethod;

        return $this;
    }

    public function decorators(): array
    {
        return $this->messageDecorators;
    }

    public function withDecorators(string|MessageDecorator ...$messageDecorators): self
    {
        $this->messageDecorators = array_merge($this->messageDecorators, $messageDecorators);

        return $this;
    }

    public function subscribers(): array
    {
        return $this->messageSubscribers;
    }

    public function withSubscribers(string|MessageSubscriber ...$messageSubscribers): self
    {
        $this->messageSubscribers = array_merge($this->messageSubscribers, $messageSubscribers);

        return $this;
    }

    public function strategy(): ProducerStrategy
    {
        if (! $this->strategy instanceof ProducerStrategy) {
            throw new RoutingViolation('Producer strategy can not be null');
        }

        return $this->strategy;
    }

    public function withStrategy(string $strategy): self
    {
        $producerStrategy = ProducerStrategy::tryFrom($strategy);

        if (! $producerStrategy instanceof ProducerStrategy) {
            throw new RoutingViolation('Invalid message producer key: unknown_strategy');
        }

        $this->strategy = $producerStrategy;

        return $this;
    }

    public function producerId(): ?string
    {
        return $this->producerId;
    }

    public function withProducerId(string $producerId): self
    {
        $this->producerId = $producerId;

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

    public function addRule(RoutingRule ...$rules): self
    {
        $this->rules = array_merge($this->rules, $rules);

        return $this;
    }

    /**
     * @return array<RoutingRule>
     */
    public function rules(): array
    {
        return $this->rules;
    }

    public function jsonSerialize(): array
    {
        return [
            $this->getType()->value => [
                $this->name => [
                    'group' => [
                        'service_id' => $this->producerId(),
                        'concrete' => $this->reporterConcrete(),
                        'tracker_id' => $this->trackerId(),
                        'handler_method_name' => $this->handlerMethod(),
                        'message_decorators' => $this->decorators(),
                        'message_subscribers' => $this->subscribers(),
                        'producer_strategy' => $this->strategy()->value,
                        'producer_service_id' => $this->producerId(),
                        'queue' => $this->queue(),
                    ],
                    'routes' => $this->routes->jsonSerialize(),
                ],
            ],
        ];
    }

    abstract public function getType(): DomainType;
}
