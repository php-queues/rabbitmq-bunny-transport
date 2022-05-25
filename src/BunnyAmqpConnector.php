<?php

declare(strict_types=1);

namespace PhpQueues\RabbitmqBunnyTransport;

use Bunny\Channel;
use Bunny\Client;
use PhpQueues\RabbitmqTransport\AmqpProducer;
use PhpQueues\RabbitmqTransport\Connection\AmqpConnector;
use PhpQueues\RabbitmqTransport\TransportConfigurator;
use PhpQueues\Transport\Consumer;
use PhpQueues\Transport\Producer;

final class BunnyAmqpConnector extends AmqpConnector
{
    private ?Client $client = null;
    private ?Channel $channel = null;

    public function configurator(): TransportConfigurator
    {
        return new TransportConfigurator(new BunnyAmqpOperator($this->channel()), $this->logger());
    }

    /**
     * {@inheritdoc}
     */
    public function producer(): Producer
    {
        return new AmqpProducer(new BunnyPublisher($this->channel()));
    }

    public function consumer(): Consumer
    {
        return new BunnyConsumer($this->channel());
    }

    public function stop(): void
    {
        if ($this->channel !== null) {
            $this->channel->close();
        }

        if ($this->client !== null) {
            $this->client->disconnect();
        }

        [$this->channel, $this->client] = [null, null];
    }

    private function channel(): Channel
    {
        if ($this->channel === null) {
            /** @var Channel */
            $this->channel = $this->client()->channel();
        }

        return $this->channel;
    }

    private function client(): Client
    {
        if ($this->client === null) {
            $options = $this->context()->dsn->parsedParameters;

            $connectionOptions = [
                'host' => $options['host'],
                'port' => $options['port'],
                'vhost' => $options['vhost'] ?? '/',
                'user' => $options['user'] ?? 'guest',
                'password' => $options['password'] ?? 'guest',
                'heartbeat' => $options['heartbeat'] ?? 60.0,
                'timeout' => $options['timeout'] ?? 1,
            ];

            if ($options['scheme'] === 'amqps') {
                if (isset($options['ssl']) === false) {
                    throw new \InvalidArgumentException('Ssl options must be configured if amqps connection used.');
                }

                $connectionOptions = \array_merge($connectionOptions, ['ssl' => $options['ssl']]);
            }

            $this->client = new Client($connectionOptions);
            $this->client->connect();
        }

        return $this->client;
    }
}
