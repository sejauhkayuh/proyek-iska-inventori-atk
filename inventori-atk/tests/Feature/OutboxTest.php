<?php

namespace Tests\Feature;

use App\Messaging\RabbitPublisher;
use App\Models\Barang;
use App\Models\Outbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Transactional outbox: pesan dicatat bersama perubahan data,
 * lalu diterbitkan oleh outbox:relay.
 */
class OutboxTest extends TestCase
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

    private function ajukan(int $jumlah = 2): void
    {
        $this->postJson('/api/v1/permintaan', [
            'pemohon' => 'Budi Santoso',
            'bagian' => 'Finance',
            'kode_barang' => 'ATK-001',
            'jumlah' => $jumlah,
        ])->assertCreated();
    }

    public function test_pengajuan_mencatat_satu_pesan_tertunda(): void
    {
        $this->barangContoh();
        $this->ajukan();

        $this->assertSame(1, Outbox::count());
        $pesan = Outbox::first();
        $this->assertSame('PermintaanAtkDiajukan', $pesan->jenis);
        $this->assertSame('permintaan.diajukan', $pesan->routing_key);
        $this->assertNull($pesan->dikirim_pada);
        $this->assertSame('PRM-0001', $pesan->payload['nomor']);
    }

    public function test_persetujuan_mencatat_event_permintaan_dan_event_stok(): void
    {
        $this->barangContoh(stok: 10);
        $this->ajukan(jumlah: 2);

        $this->postJson('/api/v1/permintaan/PRM-0001/persetujuan', ['disetujui_oleh' => 'Petugas Gudang'])
            ->assertOk();

        $this->assertSame(
            ['permintaan.diajukan', 'stok.barang_keluar', 'permintaan.disetujui'],
            Outbox::orderBy('id')->pluck('routing_key')->all(),
        );
        $stok = Outbox::where('routing_key', 'stok.barang_keluar')->first();
        $this->assertSame(8, $stok->payload['stok_sesudah']);
        $this->assertSame(5, $stok->payload['stok_minimum']);
    }

    public function test_operasi_yang_ditolak_tidak_mencatat_pesan(): void
    {
        $this->barangContoh(stok: 1);

        $this->postJson('/api/v1/barang/ATK-001/penyesuaian-stok', [
            'perubahan' => -5,
            'alasan' => 'barang_keluar',
        ])->assertStatus(409);

        $this->assertSame(0, Outbox::count());
        $this->assertSame(1, Barang::where('kode', 'ATK-001')->value('stok'));
    }

    public function test_relay_menerbitkan_pesan_tertunda_lalu_menandainya(): void
    {
        $this->barangContoh();
        $this->ajukan();

        $this->mock(RabbitPublisher::class, function ($mock) {
            $mock->shouldReceive('publish')
                ->once()
                ->with('permintaan.diajukan', Mockery::type('array'), Mockery::type('string'), 'PermintaanAtkDiajukan');
        });

        $this->artisan('outbox:relay')->assertSuccessful();

        $this->assertNotNull(Outbox::first()->dikirim_pada);
    }

    public function test_relay_tidak_mengirim_ulang_pesan_yang_sudah_terkirim(): void
    {
        $this->barangContoh();
        $this->ajukan();
        Outbox::query()->update(['dikirim_pada' => now()]);

        $this->mock(RabbitPublisher::class, function ($mock) {
            $mock->shouldNotReceive('publish');
        });

        $this->artisan('outbox:relay')->assertSuccessful();
    }

    public function test_saat_broker_mati_pesan_tetap_tertunda_dan_relay_berhenti(): void
    {
        $this->barangContoh();
        $this->ajukan();
        $this->ajukan();

        $this->mock(RabbitPublisher::class, function ($mock) {
            $mock->shouldReceive('publish')->once()->andThrow(new RuntimeException('broker mati'));
        });

        $this->artisan('outbox:relay')->assertSuccessful();

        $this->assertSame(2, Outbox::whereNull('dikirim_pada')->count());
    }
}
