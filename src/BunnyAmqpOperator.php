<?php

declare(strict_types=1);

namespace PhpQueues\RabbitmqBunnyTransport;

use PhpQueues\RabbitmqTransport\AmqpOperator;
use PhpQueues\RabbitmqTransport\Exchange;
use PhpQueues\RabbitmqTransport\Queue;
use Bunny\Channel;

final class BunnyAmqpOperator implements AmqpOperator
{
    private Channel $channel;

    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
    }

    public function declareQueue(Queue $queue): void
    {
        $this->channel->queueDeclare($queue->name, $queue->passive, $queue->durable, $queue->exclusive, $queue->autoDelete, $queue->noWait, $queue->arguments);
    }

    public function declareExchange(Exchange $exchange): void
    {
        $this->channel->exchangeDeclare($exchange->name, $exchange->type->value, $exchange->passive, $exchange->durable, $exchange->autoDelete, $exchange->internal, $exchange->noWait, $exchange->arguments);
    }

    public function bindQueue(Queue $queue, Exchange $exchange, string $routingKey, bool $noWait = false, array $arguments = []): void
    {
        $this->channel->queueBind($queue->name, $exchange->name, $routingKey, $noWait, $arguments);
    }

    public function bindExchange(Exchange $source, Exchange $destination, string $routingKey, bool $noWait = false, array $arguments = []): void
    {
        $this->channel->exchangeBind($destination->name, $source->name, $routingKey, $noWait, $arguments);
    }
}
