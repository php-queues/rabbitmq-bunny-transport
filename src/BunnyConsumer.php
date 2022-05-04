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

    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
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
        /** @var array{x-trace-id:non-empty-string} $headers */
        $headers = $message->headers;

        /** @psalm-var non-empty-string $content */
        $content = $message->content;

        return new AmqpPackage(
            $headers['x-trace-id'],
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
