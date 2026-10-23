<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'barang';

    protected $fillable = ['kode', 'nama', 'satuan', 'stok', 'stok_minimum'];

    public function getRouteKeyName(): string
    {
        return 'kode';
    }
}
