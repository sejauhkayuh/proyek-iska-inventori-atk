<?php

namespace App\Messaging;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Menerbitkan satu pesan ke exchange atk.events (topic, durable).
 * Pesan persistent, membawa message_id, type, dan timestamp.
 * Memakai publisher confirm: bila broker tidak menerima, exception dilempar.
 */
class RabbitPublisher
{
    public function publish(string $routingKey, array $payload, string $messageId, string $type): void
    {
        $cfg = config('rabbitmq');

        $connection = new AMQPStreamConnection(
            $cfg['host'], $cfg['port'], $cfg['user'], $cfg['password'], $cfg['vhost'],
        );

        try {
            $channel = $connection->channel();
            $channel->exchange_declare($cfg['exchange'], 'topic', false, true, false);
            $channel->confirm_select();

            $message = new AMQPMessage(
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'message_id' => $messageId,
                    'type' => $type,
                    'timestamp' => time(),
                ],
            );

            $channel->basic_publish($message, $cfg['exchange'], $routingKey);
            $channel->wait_for_pending_acks(5.0);
            $channel->close();
        } finally {
            $connection->close();
        }
    }
}
