<?php

namespace Tests\Feature;

use App\Models\LogNotifikasi;
use App\Notifikasi\PenangananNotifikasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PenangananNotifikasiTest extends TestCase
{
    use RefreshDatabase;

    private function diajukan(): array
    {
        return [
            'nomor' => 'PRM-0001',
            'kode_barang' => 'ATK-001',
            'jumlah' => 2,
            'pemohon' => 'Budi Santoso',
            'bagian' => 'Finance',
        ];
    }

    private function stok(int $sesudah, int $minimum = 5): array
    {
        return [
            'kode' => 'ATK-001',
            'perubahan' => -3,
            'alasan' => 'barang_keluar',
            'stok_sesudah' => $sesudah,
            'stok_minimum' => $minimum,
        ];
    }

    public function test_permintaan_diajukan_dicatat_untuk_petugas_gudang(): void
    {
        $baru = app(PenangananNotifikasi::class)
            ->tangani('11111111-1111-1111-1111-111111111111', 'permintaan.diajukan', $this->diajukan());

        $this->assertTrue($baru);
        $log = LogNotifikasi::first();
        $this->assertSame('permintaan-baru', $log->jenis);
        $this->assertSame('petugas-gudang', $log->penerima);
        $this->assertStringContainsString('PRM-0001', $log->isi);
    }

    public function test_pesan_dengan_message_id_sama_tidak_dicatat_dua_kali(): void
    {
        $handler = app(PenangananNotifikasi::class);
        $id = '22222222-2222-2222-2222-222222222222';

        $this->assertTrue($handler->tangani($id, 'permintaan.diajukan', $this->diajukan()));
        $this->assertFalse($handler->tangani($id, 'permintaan.diajukan', $this->diajukan()));

        $this->assertSame(1, LogNotifikasi::count());
    }

    public function test_pesan_tanpa_field_wajib_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(PenangananNotifikasi::class)
            ->tangani('33333333-3333-3333-3333-333333333333', 'permintaan.diajukan', ['nomor' => 'PRM-0001']);
    }

    public function test_pesan_tanpa_message_id_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(PenangananNotifikasi::class)->tangani(null, 'permintaan.diajukan', $this->diajukan());
    }

    public function test_stok_di_bawah_minimum_menjadi_peringatan_menipis(): void
    {
        app(PenangananNotifikasi::class)
            ->tangani('44444444-4444-4444-4444-444444444444', 'stok.barang_keluar', $this->stok(sesudah: 4));

        $this->assertSame('stok-menipis', LogNotifikasi::first()->jenis);
    }

    public function test_stok_aman_hanya_dicatat_sebagai_info(): void
    {
        app(PenangananNotifikasi::class)
            ->tangani('55555555-5555-5555-5555-555555555555', 'stok.barang_keluar', $this->stok(sesudah: 20));

        $this->assertSame('info-stok', LogNotifikasi::first()->jenis);
    }
}
