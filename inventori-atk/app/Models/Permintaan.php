<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permintaan extends Model
{
    protected $table = 'permintaan';

    protected $fillable = [
        'nomor', 'kode_barang', 'jumlah', 'pemohon', 'bagian', 'status', 'disetujui_oleh',
    ];

    public function getRouteKeyName(): string
    {
        return 'nomor';
    }
}
