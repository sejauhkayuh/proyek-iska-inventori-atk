<?php

namespace Tests\Feature;

use App\Models\Barang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Setiap test di bawah ini menerjemahkan satu janji pada openapi.yaml
 * (operasi barang) menjadi pemeriksaan yang bisa dijalankan.
 */
class BarangApiTest extends TestCase
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

    public function test_daftar_barang_mengembalikan_200_dan_larik_data(): void
    {
        $this->barangContoh();

        $this->getJson('/api/v1/barang')
            ->assertOk()
            ->assertJsonStructure(['data' => [['kode', 'nama', 'satuan', 'stok', 'stok_minimum', 'diperbarui_pada']]]);
    }

    public function test_menambah_barang_mengembalikan_201_dan_header_location(): void
    {
        $this->postJson('/api/v1/barang', [
            'kode' => 'ATK-010',
            'nama' => 'Spidol Whiteboard Hitam',
            'satuan' => 'pcs',
            'stok_minimum' => 5,
        ])
            ->assertCreated()
            ->assertHeader('Location', url('/api/v1/barang/ATK-010'))
            ->assertJsonPath('data.stok', 0);
    }

    public function test_barang_tanpa_nama_ditolak_dengan_422_problem_json(): void
    {
        $this->postJson('/api/v1/barang', [
            'kode' => 'ATK-010',
            'satuan' => 'pcs',
            'stok_minimum' => 5,
        ])
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('status', 422)
            ->assertJsonStructure(['type', 'title', 'status', 'detail', 'errors' => ['nama']]);
    }

    public function test_kode_barang_yang_tidak_ada_mengembalikan_404_problem_json(): void
    {
        $this->getJson('/api/v1/barang/XXX-999')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('status', 404);
    }

    public function test_penyesuaian_stok_mengubah_stok(): void
    {
        $this->barangContoh(stok: 10);

        $this->postJson('/api/v1/barang/ATK-001/penyesuaian-stok', [
            'perubahan' => -3,
            'alasan' => 'barang_keluar',
        ])
            ->assertOk()
            ->assertJsonPath('data.stok', 7);
    }

    public function test_pengurangan_melebihi_stok_ditolak_dengan_409(): void
    {
        $this->barangContoh(stok: 3);

        $this->postJson('/api/v1/barang/ATK-001/penyesuaian-stok', [
            'perubahan' => -5,
            'alasan' => 'barang_keluar',
        ])
            ->assertStatus(409)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'https://inventori-atk.test/problem/stok-tidak-cukup')
            ->assertJsonPath('stok_tersedia', 3);

        $this->assertSame(3, Barang::where('kode', 'ATK-001')->value('stok'));
    }
}
