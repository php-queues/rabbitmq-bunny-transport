<?php

declare(strict_types=1);

namespace PhpQueues\RabbitmqBunnyTransport;

use Bunny\Channel;
use PhpQueues\RabbitmqTransport\AmqpMessage;
use PhpQueues\RabbitmqTransport\PackagePublisher;

final class BunnyPublisher implements PackagePublisher
{
    private Channel $channel;

    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
    }

    /**
     * {@inheritdoc}
     */
    public function publish(AmqpMessage ...$messages): void
    {
        if (\count($messages) > 1) {
            $this->transactional($this->channel, ...$messages);

            return;
        }

        $this->doPublish($this->channel, $messages[0]);
    }

    private function transactional(Channel $channel, AmqpMessage ...$messages): void
    {
        $channel->txSelect();

        try {
            foreach ($messages as $message) {
                $this->doPublish($channel, $message);
            }

            $channel->txCommit();
        } catch (\Throwable $e) {
            $channel->txRollback();

            throw $e;
        } finally {
            $channel->close();
        }
    }

    private function doPublish(Channel $channel, AmqpMessage $message): void
    {
        $message = $message->withHeaders(['x-trace-id' => $message->id]);

        $channel->publish($message->payload, $message->headers, $message->destination->exchange, $message->destination->routingKey, $message->mandatory, $message->immediate);
    }
}
