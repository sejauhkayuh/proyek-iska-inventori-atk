<?php

namespace App\Console\Commands;

use App\Messaging\Konsumen;
use Illuminate\Console\Command;

class NotifikasiPermintaan extends Command
{
    protected $signature = 'notifikasi:permintaan';

    protected $description = 'Consumer notifikasi permintaan ATK (antrian notifikasi.permintaan, dengan DLQ)';

    public function handle(Konsumen $konsumen): int
    {
        $konsumen->jalankan('notifikasi.permintaan', 'permintaan.*', true, $this);

        return self::SUCCESS;
    }
}
