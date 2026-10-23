<?php

use App\Http\Controllers\Api\BarangController;
use App\Http\Controllers\Api\PermintaanController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/barang', [BarangController::class, 'index']);
    Route::post('/barang', [BarangController::class, 'store']);
    Route::get('/barang/{barang:kode}', [BarangController::class, 'show']);
    Route::post('/barang/{barang:kode}/penyesuaian-stok', [BarangController::class, 'sesuaikanStok']);

    Route::post('/permintaan', [PermintaanController::class, 'store']);
    Route::get('/permintaan/{permintaan:nomor}', [PermintaanController::class, 'show']);
    Route::post('/permintaan/{permintaan:nomor}/persetujuan', [PermintaanController::class, 'setujui']);
});
