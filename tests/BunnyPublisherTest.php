<?php

declare(strict_types=1);

namespace PhpQueues\RabbitmqBunnyTransport\Tests;

use Bunny\Channel;
use PhpQueues\RabbitmqBunnyTransport\BunnyPublisher;
use PhpQueues\RabbitmqTransport\AmqpDestination;
use PhpQueues\RabbitmqTransport\AmqpMessage;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class BunnyPublisherTest extends TestCase
{
    public function testPublishedSingle(): void
    {
        /** @psalm-var non-empty-string $id */
        $id = \uniqid();

        $message = new AmqpMessage($id, '{"name": "test"}', new AmqpDestination('develop', 'test'));

        $channel = $this->createMock(Channel::class);

        $channel
            ->expects($this->exactly(1))
            ->method('publish')
            ->with($message->payload, $message->withHeaders(['x-trace-id' => $id])->headers, $message->destination->exchange, $message->destination->routingKey, $message->mandatory, $message->immediate)
        ;

        $publisher = new BunnyPublisher($channel);
        $publisher->publish($message);
    }

    public function testPublishedTransactional(): void
    {
        /** @psalm-var non-empty-string $id1 */
        $id1 = \uniqid();

        $message1 = new AmqpMessage($id1, '{"name": "test"}', new AmqpDestination('develop', 'test'));

        /** @psalm-var non-empty-string $id2 */
        $id2 = \uniqid();

        $message2 = new AmqpMessage($id2, '{"name": "test"}', new AmqpDestination('develop', 'test'));

        $channel = $this->createMock(Channel::class);

        $channel
            ->expects($this->exactly(1))
            ->method('txSelect');

        $channel
            ->expects($this->exactly(1))
            ->method('txCommit');

        $channel
            ->expects($this->exactly(2))
            ->method('publish')
            ->withConsecutive(
                [
                    $this->equalTo($message1->payload),
                    $this->equalTo($message1->withHeaders(['x-trace-id' => $id1])->headers),
                    $this->equalTo($message1->destination->exchange),
                    $this->equalTo($message1->destination->routingKey),
                    $this->equalTo($message1->mandatory),
                    $this->equalTo($message1->immediate),
                ],
                [
                    $this->equalTo($message2->payload),
                    $this->equalTo($message2->withHeaders(['x-trace-id' => $id2])->headers),
                    $this->equalTo($message2->destination->exchange),
                    $this->equalTo($message2->destination->routingKey),
                    $this->equalTo($message2->mandatory),
                    $this->equalTo($message2->immediate),
                ],
            )
        ;

        $publisher = new BunnyPublisher($channel);
        $publisher->publish($message1, $message2);
    }

    public function testTransactionRollback(): void
    {
        /** @psalm-var non-empty-string $id1 */
        $id1 = \uniqid();

        $message1 = new AmqpMessage($id1, '{"name": "test"}', new AmqpDestination('develop', 'test'));

        /** @psalm-var non-empty-string $id2 */
        $id2 = \uniqid();

        $message2 = new AmqpMessage($id2, '{"name": "test"}', new AmqpDestination('develop', 'test'));

        $channel = $this->createMock(Channel::class);

        $channel
            ->expects($this->exactly(1))
            ->method('txSelect');

        $channel
            ->expects($this->exactly(1))
            ->method('txRollback');

        $channel
            ->expects($this->exactly(1))
            ->method('publish')
            ->willThrowException(new \RuntimeException());

        $publisher = new BunnyPublisher($channel);

        self::expectException(\RuntimeException::class);
        $publisher->publish($message1, $message2);
    }
}
