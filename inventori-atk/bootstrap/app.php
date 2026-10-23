<?php

use App\Exceptions\BarangTidakDitemukan;
use App\Exceptions\PermintaanSudahDiproses;
use App\Exceptions\StokTidakCukup;
use App\Support\Problem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport([
            StokTidakCukup::class,
            PermintaanSudahDiproses::class,
            BarangTidakDitemukan::class,
        ]);

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return Problem::response(
                    422,
                    'validasi-gagal',
                    'Isi permintaan tidak valid',
                    'Satu atau lebih field tidak memenuhi kontrak.',
                    '/'.$request->path(),
                    ['errors' => $e->errors()],
                );
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return Problem::response(
                    404,
                    'tidak-ditemukan',
                    'Sumber daya tidak ditemukan',
                    'Tidak ada sumber daya pada alamat ini.',
                    '/'.$request->path(),
                );
            }
        });

        $exceptions->render(function (BarangTidakDitemukan $e, Request $request) {
            return Problem::response(
                404,
                'barang-tidak-ditemukan',
                'Barang tidak ditemukan',
                $e->getMessage(),
                '/'.$request->path(),
            );
        });

        $exceptions->render(function (StokTidakCukup $e, Request $request) {
            return Problem::response(
                409,
                'stok-tidak-cukup',
                'Stok tidak cukup',
                $e->getMessage(),
                '/'.$request->path(),
                ['stok_tersedia' => $e->tersedia],
            );
        });

        $exceptions->render(function (PermintaanSudahDiproses $e, Request $request) {
            return Problem::response(
                409,
                'permintaan-sudah-diproses',
                'Permintaan sudah diproses',
                $e->getMessage(),
                '/'.$request->path(),
            );
        });
    })->create();
