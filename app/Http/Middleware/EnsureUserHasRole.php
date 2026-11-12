<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Проверка роли: ->middleware('role:admin'). */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Требуется авторизация.'], 401);
        }
        if (! in_array($user->role->value, $roles, true)) {
            return response()->json(['message' => 'Недостаточно прав для выполнения действия.'], 403);
        }

        return $next($request);
    }
}
