<?php

namespace App\Messaging;

use App\Notifikasi\PenangananNotifikasi;
use Illuminate\Console\Command;
use InvalidArgumentException;
use JsonException;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Throwable;

/**
 * Consumer RabbitMQ dengan manual ack.
 * - Berhasil atau duplikat: ack.
 * - Pesan tidak valid (JSON rusak, field wajib hilang): nack tanpa requeue -> DLQ (bila antrian punya DLX).
 * - Gangguan sementara (mis. basis data mati): nack dengan requeue.
 */
class Konsumen
{
    public function __construct(private readonly PenangananNotifikasi $handler)
    {
    }

    public function jalankan(string $antrian, string $binding, bool $denganDlq, Command $cmd): void
    {
        $cfg = config('rabbitmq');

        $connection = new AMQPStreamConnection(
            $cfg['host'], $cfg['port'], $cfg['user'], $cfg['password'], $cfg['vhost'],
        );
        $channel = $connection->channel();

        $channel->exchange_declare($cfg['exchange'], 'topic', false, true, false);

        $argumen = [];
        if ($denganDlq) {
            $channel->exchange_declare('atk.dlx', 'fanout', false, true, false);
            $channel->queue_declare("{$antrian}.dlq", false, true, false, false);
            $channel->queue_bind("{$antrian}.dlq", 'atk.dlx');
            $argumen = new AMQPTable(['x-dead-letter-exchange' => 'atk.dlx']);
        }

        $channel->queue_declare($antrian, false, true, false, false, false, $argumen);
        $channel->queue_bind($antrian, $cfg['exchange'], $binding);
        $channel->basic_qos(0, 1, false);

        $cmd->info("Menunggu pesan di antrian {$antrian} (binding {$binding}). Ctrl+C untuk berhenti.");

        $callback = function (AMQPMessage $pesan) use ($cmd) {
            $routingKey = method_exists($pesan, 'getRoutingKey')
                ? $pesan->getRoutingKey()
                : ($pesan->delivery_info['routing_key'] ?? '');
            $messageId = $pesan->has('message_id') ? $pesan->get('message_id') : null;

            try {
                $payload = json_decode($pesan->getBody(), true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($payload)) {
                    throw new InvalidArgumentException('Payload bukan objek JSON');
                }

                $baru = $this->handler->tangani($messageId, $routingKey, $payload);
                $cmd->info(($baru ? 'Dicatat' : 'Dilewati (duplikat)')." [{$routingKey}] {$messageId}");
                $pesan->ack();
            } catch (JsonException|InvalidArgumentException $e) {
                $cmd->error("Pesan ditolak: {$e->getMessage()}");
                $pesan->nack(false, false);
            } catch (Throwable $e) {
                $cmd->error("Gagal sementara, dikembalikan ke antrian: {$e->getMessage()}");
                sleep(2);
                $pesan->nack(false, true);
            }
        };

        $channel->basic_consume($antrian, '', false, false, false, false, $callback);

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}
