<?php

declare(strict_types=1);

namespace PhpQueues\RabbitmqBunnyTransport\Tests;

use Bunny\Channel;
use PhpQueues\RabbitmqBunnyTransport\BunnyAmqpOperator;
use PhpQueues\RabbitmqTransport\Exchange;
use PhpQueues\RabbitmqTransport\Queue;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class BunnyAmqpOperatorTest extends TestCase
{
    public function testQueueDeclared(): void
    {
        $queue = Queue::default('test')->makeDurable()->withArguments(['x-name' => 'x-value']);

        $channel = $this->createMock(Channel::class);

        $channel
            ->expects($this->exactly(1))
            ->method('queueDeclare')
            ->with($queue->name, $queue->passive, $queue->durable, $queue->exclusive, $queue->autoDelete, $queue->noWait, $queue->arguments)
        ;

        $operator = new BunnyAmqpOperator($channel);
        $operator->declareQueue($queue);
    }

    public function testExchangeDeclared(): void
    {
        $exchange = Exchange::direct('test')->makeDurable()->withArguments(['x-name' => 'x-value']);

        $channel = $this->createMock(Channel::class);

        $channel
            ->expects($this->exactly(1))
            ->method('exchangeDeclare')
            ->with($exchange->name, $exchange->type->value, $exchange->passive, $exchange->durable, $exchange->autoDelete, $exchange->internal, $exchange->noWait, $exchange->arguments)
        ;

        $operator = new BunnyAmqpOperator($channel);
        $operator->declareExchange($exchange);
    }

    public function testQueueBind(): void
    {
        $queue = Queue::default('test')->makeDurable()->withArguments(['x-name' => 'x-value']);
        $exchange = Exchange::direct('test')->makeDurable()->withArguments(['x-name' => 'x-value']);

        $channel = $this->createMock(Channel::class);

        $channel
            ->expects($this->exactly(1))
            ->method('queueBind')
            ->with($queue->name, $exchange->name, 'test', true, ['x-argument' => 'x-argument-value'])
        ;

        $operator = new BunnyAmqpOperator($channel);
        $operator->bindQueue($queue, $exchange, 'test', true, ['x-argument' => 'x-argument-value']);
    }

    public function testExchangeBind(): void
    {
        $source = Exchange::delayed('delayed_test');
        $destination = Exchange::direct('test')->makeDurable()->withArguments(['x-name' => 'x-value']);

        $channel = $this->createMock(Channel::class);

        $channel
            ->expects($this->exactly(1))
            ->method('exchangeBind')
            ->with($destination->name, $source->name, 'test', true, ['x-argument' => 'x-argument-value'])
        ;

        $operator = new BunnyAmqpOperator($channel);
        $operator->bindExchange($source, $destination, 'test', true, ['x-argument' => 'x-argument-value']);
    }
}
