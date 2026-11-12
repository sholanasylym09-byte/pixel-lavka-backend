<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Журнал запросов к API: метод, URL, пользователь, код ответа, время.
 * Тело запроса (пароли, токены) в журнал не пишется.
 */
class LogApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = $next($request);

        Log::channel('api')->info(sprintf(
            '%s %s user=%s status=%d time=%dms',
            $request->method(),
            $request->path(),
            $request->user('sanctum')?->id ?? 'guest',
            $response->getStatusCode(),
            (int) round((microtime(true) - $start) * 1000)
        ));

        return $response;
    }
}
