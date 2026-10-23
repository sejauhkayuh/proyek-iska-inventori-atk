<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Pembuat respons Problem Details (RFC 9457).
 */
class Problem
{
    public const BASE = 'https://inventori-atk.test/problem/';

    public static function response(
        int $status,
        string $slug,
        string $title,
        string $detail,
        string $instance,
        array $extra = [],
    ): JsonResponse {
        return response()->json(
            array_merge([
                'type' => self::BASE.$slug,
                'title' => $title,
                'status' => $status,
                'detail' => $detail,
                'instance' => $instance,
            ], $extra),
            $status,
            ['Content-Type' => 'application/problem+json'],
        );
    }
}
