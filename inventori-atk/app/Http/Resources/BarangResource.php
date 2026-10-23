<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representasi barang persis seperti yang dijanjikan pada openapi.yaml.
 */
class BarangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'kode' => $this->kode,
            'nama' => $this->nama,
            'satuan' => $this->satuan,
            'stok' => $this->stok,
            'stok_minimum' => $this->stok_minimum,
            'diperbarui_pada' => $this->updated_at?->toIso8601String(),
        ];
    }
}
