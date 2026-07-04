<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class HealthController
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'application' => 'ok',
            'database' => 'failed',
            'redis' => 'failed',
        ];

        try {
            DB::select('SELECT 1');
            $checks['database'] = 'ok';
        } catch (Throwable) {
            $checks['database'] = 'failed';
        }

        try {
            Redis::connection()->ping();
            $checks['redis'] = 'ok';
        } catch (Throwable) {
            $checks['redis'] = 'failed';
        }

        $healthy = ! in_array('failed', $checks, true);

        return response()->json(
            [
                'status' => $healthy ? 'ok' : 'degraded',
                'checks' => $checks,
                'timestamp' => now()->toIso8601String(),
            ],
            $healthy ? 200 : 503,
        );
    }
}
