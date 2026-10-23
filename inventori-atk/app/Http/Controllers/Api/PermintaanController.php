<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BarangTidakDitemukan;
use App\Exceptions\PermintaanSudahDiproses;
use App\Http\Controllers\Controller;
use App\Http\Requests\AjukanPermintaanRequest;
use App\Http\Requests\PersetujuanPermintaanRequest;
use App\Http\Resources\PermintaanResource;
use App\Models\Barang;
use App\Models\Outbox;
use App\Models\Permintaan;
use App\Services\StokService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PermintaanController extends Controller
{
    public function __construct(private readonly StokService $stok)
    {
    }

    public function store(AjukanPermintaanRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (! Barang::where('kode', $data['kode_barang'])->exists()) {
            throw new BarangTidakDitemukan($data['kode_barang']);
        }

        $permintaan = DB::transaction(function () use ($data) {
            $nomor = sprintf('PRM-%04d', ((int) Permintaan::max('id')) + 1);

            $permintaan = Permintaan::create($data + [
                'nomor' => $nomor,
                'status' => 'diajukan',
            ]);

            Outbox::catat('PermintaanAtkDiajukan', 'permintaan.diajukan', [
                'nomor' => $permintaan->nomor,
                'kode_barang' => $permintaan->kode_barang,
                'jumlah' => $permintaan->jumlah,
                'pemohon' => $permintaan->pemohon,
                'bagian' => $permintaan->bagian,
                'terjadi_pada' => now()->toIso8601String(),
            ]);

            return $permintaan;
        });

        return (new PermintaanResource($permintaan))
            ->response()
            ->setStatusCode(201)
            ->header('Location', url('/api/v1/permintaan/'.$permintaan->nomor));
    }

    public function show(Permintaan $permintaan): PermintaanResource
    {
        return new PermintaanResource($permintaan);
    }

    public function setujui(PersetujuanPermintaanRequest $request, Permintaan $permintaan): PermintaanResource
    {
        $disetujuiOleh = (string) $request->input('disetujui_oleh');

        $hasil = DB::transaction(function () use ($permintaan, $disetujuiOleh) {
            // Kunci baris permintaan agar dua persetujuan bersamaan tidak memotong stok dua kali.
            $p = Permintaan::where('id', $permintaan->id)->lockForUpdate()->firstOrFail();

            if ($p->status !== 'diajukan') {
                throw new PermintaanSudahDiproses($p->nomor, $p->status);
            }

            $this->stok->terapkan($p->kode_barang, -$p->jumlah, 'barang_keluar');

            $p->update(['status' => 'disetujui', 'disetujui_oleh' => $disetujuiOleh]);

            Outbox::catat('PermintaanAtkDisetujui', 'permintaan.disetujui', [
                'nomor' => $p->nomor,
                'kode_barang' => $p->kode_barang,
                'jumlah' => $p->jumlah,
                'pemohon' => $p->pemohon,
                'disetujui_oleh' => $disetujuiOleh,
                'terjadi_pada' => now()->toIso8601String(),
            ]);

            return $p;
        });

        return new PermintaanResource($hasil);
    }
}
