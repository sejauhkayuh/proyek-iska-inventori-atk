<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PenyesuaianStokRequest;
use App\Http\Requests\TambahBarangRequest;
use App\Http\Resources\BarangResource;
use App\Models\Barang;
use App\Services\StokService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class BarangController extends Controller
{
    public function __construct(private readonly StokService $stok)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return BarangResource::collection(Barang::orderBy('kode')->get());
    }

    public function store(TambahBarangRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['stok'] = $data['stok'] ?? 0;

        $barang = Barang::create($data);

        return (new BarangResource($barang))
            ->response()
            ->setStatusCode(201)
            ->header('Location', url('/api/v1/barang/'.$barang->kode));
    }

    public function show(Barang $barang): BarangResource
    {
        return new BarangResource($barang);
    }

    public function sesuaikanStok(PenyesuaianStokRequest $request, Barang $barang): BarangResource
    {
        $hasil = DB::transaction(fn () => $this->stok->terapkan(
            $barang->kode,
            (int) $request->input('perubahan'),
            (string) $request->input('alasan'),
        ));

        return new BarangResource($hasil);
    }
}
