<?php

declare(strict_types=1);

use PhpQueues\RabbitmqBunnyTransport\BunnyAmqpConnector;
use PhpQueues\RabbitmqTransport\AmqpDestination;
use PhpQueues\RabbitmqTransport\AmqpMessage;
use PhpQueues\RabbitmqTransport\Connection\ConnectionContext;
use PhpQueues\RabbitmqTransport\Connection\Dsn;
use PhpQueues\RabbitmqTransport\Delay\AmqpDelayDestination;
use PhpQueues\RabbitmqTransport\Exchange;
use PhpQueues\RabbitmqTransport\Queue;
use PhpQueues\RabbitmqTransport\QueueBinding;
use PhpQueues\Transport\Package;
use Psr\Log\NullLogger;

/** @psalm-suppress MissingFile */
require_once __DIR__.'/../vendor/autoload.php';

$connector = BunnyAmqpConnector::connect(new ConnectionContext(Dsn::fromString('amqp://jojo:secret@localhost:5673?vhost=/'), ConnectionContext::DELAY_TYPE_DELAYED_EXCHANGE), new NullLogger());

$configurator = $connector->configurator();

$exchange = Exchange::direct('develop')->makeDurable();

$configurator->bindQueue(Queue::default('orders')->makeDurable(), new QueueBinding($exchange, 'orders'));
$configurator->bindQueue(Queue::default('events')->makeDurable(), new QueueBinding($exchange, 'events'));

$producer = $connector->producer();

/** @psalm-var non-empty-string $id */
$id = \uniqid();

#### Single message publish ###
$producer->publish(new AmqpMessage($id, '{"name": "test"}', new AmqpDestination('develop', 'orders')));

#### Publish transactional ###
$producer->publish(
    new AmqpMessage($id, '{"name": "test"}', new AmqpDestination('develop', 'orders')),
    new AmqpMessage($id, '{"name": "test"}', new AmqpDestination('develop', 'events')),
);

#### Delay message ####
$delayer = $connector->delayer();

$delayer->delay(
    $producer,
    new AmqpMessage($id, '{"name": "test"}', new AmqpDestination('develop', 'events')),
    new AmqpDelayDestination('events', 'events', 'delayed_develop', '%queue_name%.delay.%ttl%'),
    10000
);

#### Consume messages
$consumer = $connector->consumer();

$consumer->consume(function (Package $package): void {
    echo $package->content();

    $package->ack();
}, 'events');

#### Get one message
$package = $consumer->once('events');
if ($package !== null) {
    echo $package->content();

    $package->ack();
}
