<?php

namespace App\Console\Commands;

use App\Messaging\RabbitPublisher;
use App\Models\Outbox;
use Illuminate\Console\Command;
use Throwable;

class OutboxRelay extends Command
{
    protected $signature = 'outbox:relay {--terus : Jalan terus-menerus} {--jeda=2 : Jeda antar putaran (detik)}';

    protected $description = 'Menerbitkan pesan outbox yang tertunda ke RabbitMQ';

    public function handle(RabbitPublisher $publisher): int
    {
        do {
            $this->kirimBatch($publisher);

            if ($this->option('terus')) {
                sleep(max(1, (int) $this->option('jeda')));
            }
        } while ($this->option('terus'));

        return self::SUCCESS;
    }

    private function kirimBatch(RabbitPublisher $publisher): void
    {
        $tertunda = Outbox::whereNull('dikirim_pada')->orderBy('id')->limit(100)->get();

        foreach ($tertunda as $pesan) {
            try {
                $publisher->publish($pesan->routing_key, $pesan->payload, $pesan->message_id, $pesan->jenis);
            } catch (Throwable $e) {
                // Berhenti di pesan pertama yang gagal agar urutan tetap terjaga.
                $this->error("Gagal menerbitkan {$pesan->jenis} ({$pesan->message_id}): {$e->getMessage()}");

                return;
            }

            $pesan->update(['dikirim_pada' => now()]);
            $this->info("Terkirim: {$pesan->jenis} [{$pesan->routing_key}] {$pesan->message_id}");
        }
    }
}
