<?php

namespace App\Notifikasi;

use App\Models\LogNotifikasi;
use InvalidArgumentException;

/**
 * Handler idempoten: pesan dengan message_id yang sama hanya dicatat satu kali,
 * sehingga pengiriman ulang (at-least-once) dari outbox relay tidak menggandakan notifikasi.
 * Notifikasi disimulasikan dengan mencatatnya ke tabel log_notifikasi.
 */
class PenangananNotifikasi
{
    private const WAJIB = [
        'permintaan.diajukan' => ['nomor', 'kode_barang', 'jumlah', 'pemohon', 'bagian'],
        'permintaan.disetujui' => ['nomor', 'kode_barang', 'jumlah', 'pemohon', 'disetujui_oleh'],
    ];

    private const WAJIB_STOK = ['kode', 'perubahan', 'alasan', 'stok_sesudah', 'stok_minimum'];

    /**
     * @return bool true bila notifikasi baru dicatat, false bila duplikat
     *
     * @throws InvalidArgumentException bila pesan tidak valid (akan masuk DLQ)
     */
    public function tangani(?string $messageId, string $routingKey, array $payload): bool
    {
        if ($messageId === null || $messageId === '') {
            throw new InvalidArgumentException('message_id wajib ada');
        }

        $wajib = str_starts_with($routingKey, 'stok.')
            ? self::WAJIB_STOK
            : (self::WAJIB[$routingKey] ?? throw new InvalidArgumentException("Routing key tidak dikenal: {$routingKey}"));

        foreach ($wajib as $field) {
            if (! array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
                throw new InvalidArgumentException("Field wajib tidak ada: {$field}");
            }
        }

        [$jenis, $penerima, $isi] = $this->susun($routingKey, $payload);

        $log = LogNotifikasi::firstOrCreate(
            ['message_id' => $messageId],
            ['jenis' => $jenis, 'penerima' => $penerima, 'isi' => $isi, 'dikirim_pada' => now()],
        );

        return $log->wasRecentlyCreated;
    }

    private function susun(string $routingKey, array $p): array
    {
        if ($routingKey === 'permintaan.diajukan') {
            return [
                'permintaan-baru',
                'petugas-gudang',
                "Permintaan baru {$p['nomor']}: {$p['pemohon']} ({$p['bagian']}) meminta {$p['jumlah']} x {$p['kode_barang']}.",
            ];
        }

        if ($routingKey === 'permintaan.disetujui') {
            return [
                'permintaan-disetujui',
                (string) $p['pemohon'],
                "Permintaan {$p['nomor']} ({$p['jumlah']} x {$p['kode_barang']}) disetujui oleh {$p['disetujui_oleh']}.",
            ];
        }

        if ((int) $p['stok_sesudah'] <= (int) $p['stok_minimum']) {
            return [
                'stok-menipis',
                'purchasing',
                "Stok {$p['kode']} menipis: tersisa {$p['stok_sesudah']} (minimum {$p['stok_minimum']}).",
            ];
        }

        return [
            'info-stok',
            'purchasing',
            "Stok {$p['kode']} berubah {$p['perubahan']} ({$p['alasan']}), kini {$p['stok_sesudah']}.",
        ];
    }
}
