<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function __invoke(StatsService $stats): JsonResponse
    {
        return response()->json(['data' => $stats->summary()]);
    }
}
