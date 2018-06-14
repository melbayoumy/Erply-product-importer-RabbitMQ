<?php

require_once(__DIR__ . '/Product.php');
require_once(__DIR__ . '/../vendor/php-amqplib/config.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;

$exchange = 'products';
$queue = 'add_products';
$consumerTag = 'consumer';
$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();
/*
    The following code is the same both in the consumer and the producer.
    In this way we are sure we always have a queue to consume from and an
        exchange where to publish messages.
*/
/*
    name: $queue
    passive: false
    durable: true // the queue will survive server restarts
    exclusive: false // the queue can be accessed in other channels
    auto_delete: false //the queue won't be deleted once the channel is closed.
*/
$channel->queue_declare($queue, false, true, false, false);
/*
    name: $exchange
    type: direct
    passive: false
    durable: true // the exchange will survive server restarts
    auto_delete: false //the exchange won't be deleted once the channel is closed.
*/
$channel->exchange_declare($exchange, 'direct', false, true, false);
$channel->queue_bind($queue, $exchange);
/**
 * @param \PhpAmqpLib\Message\AMQPMessage $message
 */
function process_message($message)
{
    echo gmdate('Y-m-d H:i:s') . ": ";
    print_r($message->body);
    echo PHP_EOL;

    $data = json_decode($message->body, true);
    
    $product = new Product();
    $product->setName($data['name']);
    $product->setPrice($data['price']);
    $product->setManufacturerName($data['manufacturerName']);
    $product->setLength($data['length']);
    $product->setWidth($data['width']);
    $product->setHeight($data['height']);
    $product->setNetWeight($data['netWeight']);
    $product->setGrossWeight($data['grossWeight']);
    $product->setDescription($data['description']);
    $product->setCost($data['cost']);

    $newProduct = $product->store();

    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    // Send a message with the string "quit" to cancel the consumer.
    if ($message->body === 'quit') {
        $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
    }
}
/*
    queue: Queue from where to get the messages
    consumer_tag: Consumer identifier
    no_local: Don't receive messages published by this consumer.
    no_ack: Tells the server if the consumer will acknowledge the messages.
    exclusive: Request exclusive consumer access, meaning only this consumer can access the queue
    nowait:
    callback: A PHP Callback
*/
$channel->basic_consume($queue, $consumerTag, false, false, false, false, 'process_message');
/**
 * @param \PhpAmqpLib\Channel\AMQPChannel $channel
 * @param \PhpAmqpLib\Connection\AbstractConnection $connection
 */
function shutdown($channel, $connection)
{
    $channel->close();
    $connection->close();
}
register_shutdown_function('shutdown', $channel, $connection);
// Loop as long as the channel has callbacks registered
while (count($channel->callbacks)) {
    $channel->wait();
}