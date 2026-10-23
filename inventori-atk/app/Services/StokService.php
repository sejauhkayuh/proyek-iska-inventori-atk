<?php

namespace App\Services;

use App\Exceptions\StokTidakCukup;
use App\Models\Barang;
use App\Models\Outbox;
use App\Models\RiwayatStok;

/**
 * Satu-satunya tempat stok diubah. Harus dipanggil di dalam DB::transaction:
 * perubahan stok, riwayat, dan pesan outbox berhasil atau gagal bersama.
 */
class StokService
{
    public function terapkan(string $kode, int $perubahan, string $alasan): Barang
    {
        $barang = Barang::where('kode', $kode)->lockForUpdate()->firstOrFail();

        $stokBaru = $barang->stok + $perubahan;
        if ($stokBaru < 0) {
            throw new StokTidakCukup($barang->kode, $barang->stok, abs($perubahan));
        }

        $barang->update(['stok' => $stokBaru]);

        RiwayatStok::create([
            'barang_id' => $barang->id,
            'perubahan' => $perubahan,
            'alasan' => $alasan,
            'stok_sesudah' => $stokBaru,
        ]);

        Outbox::catat('StokDisesuaikan', "stok.{$alasan}", [
            'kode' => $barang->kode,
            'perubahan' => $perubahan,
            'alasan' => $alasan,
            'stok_sesudah' => $stokBaru,
            'stok_minimum' => $barang->stok_minimum,
            'terjadi_pada' => now()->toIso8601String(),
        ]);

        return $barang;
    }
}
