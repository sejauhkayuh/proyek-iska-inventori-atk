<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogNotifikasi extends Model
{
    protected $table = 'log_notifikasi';

    public $timestamps = false;

    protected $fillable = ['message_id', 'jenis', 'penerima', 'isi', 'dikirim_pada'];
}
