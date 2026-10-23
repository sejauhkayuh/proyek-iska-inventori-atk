<?php

namespace App\Console\Commands;

use App\Messaging\Konsumen;
use Illuminate\Console\Command;

class NotifikasiStok extends Command
{
    protected $signature = 'notifikasi:stok';

    protected $description = 'Consumer peringatan stok menipis (antrian notifikasi.stok, tanpa DLQ)';

    public function handle(Konsumen $konsumen): int
    {
        $konsumen->jalankan('notifikasi.stok', 'stok.*', false, $this);

        return self::SUCCESS;
    }
}
