<?php

namespace App\Exceptions;

use RuntimeException;

class PermintaanSudahDiproses extends RuntimeException
{
    public function __construct(public readonly string $nomor, public readonly string $status)
    {
        parent::__construct("Permintaan {$nomor} sudah berstatus {$status}.");
    }
}
