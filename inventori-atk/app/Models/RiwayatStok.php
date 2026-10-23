<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatStok extends Model
{
    protected $table = 'riwayat_stok';

    public $timestamps = false;

    protected $fillable = ['barang_id', 'perubahan', 'alasan', 'stok_sesudah'];
}
