<?php

namespace App\Exceptions;

use RuntimeException;

class BarangTidakDitemukan extends RuntimeException
{
    public function __construct(public readonly string $kode)
    {
        parent::__construct("Barang dengan kode {$kode} tidak ada.");
    }
}
