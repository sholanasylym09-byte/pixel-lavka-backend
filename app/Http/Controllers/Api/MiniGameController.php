<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlayGameRequest;
use App\Http\Resources\GameResultResource;
use App\Http\Resources\MiniGameResource;
use App\Models\MiniGame;
use App\Services\GameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MiniGameController extends Controller
{
    public function __construct(private GameService $games) {}

    public function index(Request $request)
    {
        $user = $request->user('sanctum');
        $games = MiniGame::query()->where('is_active', true)->orderBy('id')->get()
            ->each(fn ($g) => $g->attempts_left = $user ? $this->games->attemptsLeft($user, $g) : null);

        return MiniGameResource::collection($games);
    }

    public function show(Request $request, MiniGame $miniGame): MiniGameResource
    {
        $user = $request->user('sanctum');
        $miniGame->attempts_left = $user ? $this->games->attemptsLeft($user, $miniGame) : null;

        return new MiniGameResource($miniGame);
    }

    /** POST /mini-games/{slug}/play — фиксирует очки и выдаёт награду. */
    public function play(PlayGameRequest $request, MiniGame $miniGame): JsonResponse
    {
        $result = $this->games->play($request->user(), $miniGame, (int) $request->validated('score'));

        return (new GameResultResource($result))->response()->setStatusCode(201);
    }
}
