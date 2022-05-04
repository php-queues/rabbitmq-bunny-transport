<?php

declare(strict_types=1);

namespace PhpQueues\RabbitmqBunnyTransport\Tests;

use Bunny\Channel;
use Bunny\Message;
use PhpQueues\RabbitmqBunnyTransport\BunnyConsumer;
use PhpQueues\RabbitmqTransport\AmqpPackage;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class BunnyConsumerTest extends TestCase
{
    public function testPackageConsumedAndAcked(): void
    {
        $channel = $this->createMock(Channel::class);

        $channel
            ->expects($this->exactly(1))
            ->method('get')
            ->with('test')
            ->willReturn(new Message(\uniqid('consumer-tag'), \uniqid('delivery-tag'), false, 'test', 'test', ['x-trace-id' => $id = \uniqid()], '{"name": "test"}'))
        ;

        $channel
            ->expects($this->exactly(1))
            ->method('ack');

        $consumer = new BunnyConsumer($channel);
        $package = $consumer->once('test');

        self::assertInstanceOf(AmqpPackage::class, $package);
        self::assertEquals($id, $package->id());
        self::assertEquals(['x-trace-id' => $id], $package->headers());
        self::assertEquals('{"name": "test"}', $package->content());
        $package->ack();
    }

    public function testPackageConsumedAndNacked(): void
    {
        $channel = $this->createMock(Channel::class);

        $channel
            ->expects($this->exactly(1))
            ->method('get')
            ->with('test')
            ->willReturn(new Message(\uniqid('consumer-tag'), \uniqid('delivery-tag'), false, 'test', 'test', ['x-trace-id' => $id = \uniqid()], '{"name": "test"}'))
        ;

        $channel
            ->expects($this->exactly(1))
            ->method('nack');

        $consumer = new BunnyConsumer($channel);
        $package = $consumer->once('test');

        self::assertInstanceOf(AmqpPackage::class, $package);
        self::assertEquals($id, $package->id());
        self::assertEquals(['x-trace-id' => $id], $package->headers());
        self::assertEquals('{"name": "test"}', $package->content());
        $package->nack(false);
    }
}
