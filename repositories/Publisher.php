<?php

require_once('../vendor/php-amqplib/config.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Publisher {

    private $exchange;
    private $queue;
    private $connection;
    private $channel;

    public function __construct($exchange, $queue) 
    {
        $this->exchange = $exchange;
        $this->queue = $queue;
    }
    
    public function initiateConnection() {
        $this->connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
        $this->channel = $this->connection->channel();

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
        $this->channel->queue_declare($this->queue, false, true, false, false);
        /*
            name: $exchange
            type: direct
            passive: false
            durable: true // the exchange will survive server restarts
            auto_delete: false //the exchange won't be deleted once the channel is closed.
        */
        $this->channel->exchange_declare($this->exchange, 'direct', false, true, false);
        $this->channel->queue_bind($this->queue, $this->exchange);
    }

    public function pushMessage($messageBody = array()) 
    {
        $messageBody = json_encode($messageBody);
        $message = new AMQPMessage($messageBody, array('content_type' => 'application/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
        $this->channel->basic_publish($message, $this->exchange);
    }

    public function terminateConnection() 
    {
        $this->channel->close();
        $this->connection->close();
    }
}