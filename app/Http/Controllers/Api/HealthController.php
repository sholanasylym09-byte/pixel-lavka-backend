<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    /** GET /api/v1/health — проверка работы API и подключения к БД. */
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('select 1');
            $db = 'ok';
        } catch (Throwable) {
            $db = 'unavailable';
        }

        return response()->json([
            'app' => config('app.name'),
            'time' => now()->toIso8601String(),
            'database' => $db,
        ], $db === 'ok' ? 200 : 503);
    }
}
