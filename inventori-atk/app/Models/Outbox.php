<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Transactional outbox: pesan dicatat di basis data yang sama dengan
 * perubahan data bisnis, dalam satu transaksi, lalu diterbitkan ke
 * RabbitMQ oleh command outbox:relay.
 */
class Outbox extends Model
{
    protected $table = 'outbox';

    public $timestamps = false;

    protected $fillable = ['message_id', 'jenis', 'routing_key', 'payload', 'dikirim_pada'];

    protected $casts = [
        'payload' => 'array',
        'dikirim_pada' => 'datetime',
    ];

    public static function catat(string $jenis, string $routingKey, array $payload): self
    {
        return self::create([
            'message_id' => (string) Str::uuid(),
            'jenis' => $jenis,
            'routing_key' => $routingKey,
            'payload' => $payload,
        ]);
    }
}
