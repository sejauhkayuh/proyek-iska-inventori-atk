<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermintaanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'nomor' => $this->nomor,
            'kode_barang' => $this->kode_barang,
            'jumlah' => $this->jumlah,
            'pemohon' => $this->pemohon,
            'bagian' => $this->bagian,
            'status' => $this->status,
            'disetujui_oleh' => $this->disetujui_oleh,
            'diperbarui_pada' => $this->updated_at?->toIso8601String(),
        ];
    }
}
