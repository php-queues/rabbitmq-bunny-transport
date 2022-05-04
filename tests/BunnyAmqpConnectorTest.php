<?php

declare(strict_types=1);

namespace PhpQueues\RabbitmqBunnyTransport\Tests;

use PhpQueues\RabbitmqBunnyTransport\BunnyAmqpConnector;
use PhpQueues\RabbitmqTransport\Connection\ConnectionContext;
use PhpQueues\RabbitmqTransport\Connection\Dsn;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class BunnyAmqpConnectorTest extends TestCase
{
    public function testSslOptionsRequiredIfSchemeIsAmqps(): void
    {
        self::expectException(\InvalidArgumentException::class);
        $connector = BunnyAmqpConnector::connect(
            new ConnectionContext(Dsn::fromString('amqps://jojo:secret@localhost:5672?vhost=/'), ConnectionContext::DELAY_TYPE_DELAYED_EXCHANGE),
            new NullLogger(),
        );
        $connector->producer();
    }
}
