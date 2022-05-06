<?php

declare(strict_types=1);

namespace PhpQueues\RabbitmqBunnyTransport;

use Bunny\Channel;
use Bunny\Message;
use PhpQueues\RabbitmqTransport\AmqpPackage;
use PhpQueues\Transport\Consumer;
use PhpQueues\Transport\Package;

final class BunnyConsumer implements Consumer
{
    private Channel $channel;

    /**
     * @psalm-var callable():non-empty-string
     */
    private $uuidGenerator;

    /**
     * @psalm-param (callable():non-empty-string)|null $uuidGenerator
     */
    public function __construct(Channel $channel, ?callable $uuidGenerator = null)
    {
        $this->channel = $channel;
        /** @psalm-var callable(): non-empty-string */
        $this->uuidGenerator = $uuidGenerator ?: fn (): string => \vsprintf('%s%s-%s-%s-%s-%s%s%s', \str_split(\bin2hex(random_bytes(16)), 4));
    }

    /**
     * {@inheritdoc}
     */
    public function consume(callable $consumer, string $queue): void
    {
        $this->channel->run(function (Message $message, Channel $channel) use ($consumer): void {
            $consumer($this->createPackage($message, $channel));
        }, $queue);
    }

    /**
     * {@inheritdoc}
     */
    public function once(string $queue): ?Package
    {
        /** @var Message|null $message */
        $message = $this->channel->get($queue);

        $package = null;

        if ($message !== null) {
            $package = $this->createPackage($message, $this->channel);
        }

        return $package;
    }

    private function createPackage(Message $message, Channel $channel): AmqpPackage
    {
        /** @var array{x-trace-id?:non-empty-string} $headers */
        $headers = $message->headers;

        /** @psalm-var non-empty-string $content */
        $content = $message->content;

        return new AmqpPackage(
            $headers['x-trace-id'] ?? ($this->uuidGenerator)(),
            $content,
            $headers,
            static function () use ($message, $channel): void {
                $channel->ack($message);
            },
            static function () use ($message, $channel): void {
                $channel->nack($message);
            },
            static function () use ($message, $channel): void {
                $channel->reject($message);
            },
        );
    }
}
