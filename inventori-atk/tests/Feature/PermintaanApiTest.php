<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Permintaan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Janji pada openapi.yaml untuk operasi permintaan ATK.
 */
class PermintaanApiTest extends TestCase
{
    use RefreshDatabase;

    private function barangContoh(int $stok = 10): Barang
    {
        return Barang::create([
            'kode' => 'ATK-001',
            'nama' => 'Kertas A4 80 gram',
            'satuan' => 'rim',
            'stok' => $stok,
            'stok_minimum' => 5,
        ]);
    }

    private function permintaanContoh(int $jumlah = 2): Permintaan
    {
        return Permintaan::create([
            'nomor' => 'PRM-0001',
            'kode_barang' => 'ATK-001',
            'jumlah' => $jumlah,
            'pemohon' => 'Budi Santoso',
            'bagian' => 'Finance',
            'status' => 'diajukan',
        ]);
    }

    public function test_mengajukan_permintaan_mengembalikan_201_location_dan_status_diajukan(): void
    {
        $this->barangContoh();

        $this->postJson('/api/v1/permintaan', [
            'pemohon' => 'Budi Santoso',
            'bagian' => 'Finance',
            'kode_barang' => 'ATK-001',
            'jumlah' => 2,
        ])
            ->assertCreated()
            ->assertHeader('Location', url('/api/v1/permintaan/PRM-0001'))
            ->assertJsonPath('data.nomor', 'PRM-0001')
            ->assertJsonPath('data.status', 'diajukan');

        // Pengajuan tidak mengurangi stok.
        $this->assertSame(10, Barang::where('kode', 'ATK-001')->value('stok'));
    }

    public function test_mengajukan_permintaan_untuk_barang_yang_tidak_ada_mengembalikan_404(): void
    {
        $this->postJson('/api/v1/permintaan', [
            'pemohon' => 'Budi Santoso',
            'bagian' => 'Finance',
            'kode_barang' => 'ATK-999',
            'jumlah' => 2,
        ])
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'https://inventori-atk.test/problem/barang-tidak-ditemukan');
    }

    public function test_nomor_permintaan_yang_tidak_ada_mengembalikan_404_problem_json(): void
    {
        $this->getJson('/api/v1/permintaan/PRM-9999')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('status', 404);
    }

    public function test_menyetujui_permintaan_mengurangi_stok_dan_mengubah_status(): void
    {
        $this->barangContoh(stok: 10);
        $this->permintaanContoh(jumlah: 2);

        $this->postJson('/api/v1/permintaan/PRM-0001/persetujuan', [
            'disetujui_oleh' => 'Petugas Gudang',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'disetujui')
            ->assertJsonPath('data.disetujui_oleh', 'Petugas Gudang');

        $this->assertSame(8, Barang::where('kode', 'ATK-001')->value('stok'));
    }

    public function test_menyetujui_permintaan_dua_kali_ditolak_dengan_409(): void
    {
        $this->barangContoh(stok: 10);
        $this->permintaanContoh(jumlah: 2);

        $this->postJson('/api/v1/permintaan/PRM-0001/persetujuan', ['disetujui_oleh' => 'Petugas Gudang'])
            ->assertOk();

        $this->postJson('/api/v1/permintaan/PRM-0001/persetujuan', ['disetujui_oleh' => 'Petugas Gudang'])
            ->assertStatus(409)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'https://inventori-atk.test/problem/permintaan-sudah-diproses');

        // Stok hanya terpotong satu kali.
        $this->assertSame(8, Barang::where('kode', 'ATK-001')->value('stok'));
    }

    public function test_menyetujui_permintaan_saat_stok_kurang_ditolak_dengan_409_dan_status_tetap(): void
    {
        $this->barangContoh(stok: 1);
        $this->permintaanContoh(jumlah: 5);

        $this->postJson('/api/v1/permintaan/PRM-0001/persetujuan', ['disetujui_oleh' => 'Petugas Gudang'])
            ->assertStatus(409)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'https://inventori-atk.test/problem/stok-tidak-cukup');

        $this->assertSame(1, Barang::where('kode', 'ATK-001')->value('stok'));
        $this->assertSame('diajukan', Permintaan::where('nomor', 'PRM-0001')->value('status'));
    }
}
