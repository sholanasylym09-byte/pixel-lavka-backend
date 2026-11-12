<?php

use App\Exceptions\BusinessException;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\LogApiRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
        $middleware->api(append: [LogApiRequests::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport([BusinessException::class]);

        // Для маршрутов API всегда отвечаем JSON
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        // Единый формат ошибок: { "message": "...", "errors": { "поле": ["..."] } }
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Ошибка валидации. Проверьте введённые данные.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Требуется авторизация.'], 401);
            }
        });

        $exceptions->render(function (BusinessException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status());
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }
            $previous = $e->getPrevious();
            $message = 'Запрашиваемый ресурс не найден.';
            if ($previous instanceof ModelNotFoundException) {
                $message = match (class_basename($previous->getModel())) {
                    'Product' => 'Товар не найден.',
                    'Category' => 'Категория не найдена.',
                    'Order' => 'Заказ не найден.',
                    'MiniGame' => 'Мини-игра не найдена.',
                    default => 'Запись не найдена.',
                };
            }

            return response()->json(['message' => $message], 404);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Доступ запрещён: это не ваш заказ.'], 403);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*')) {
                $status = $e->getStatusCode();
                $message = match ($status) {
                    403 => 'Доступ запрещён.',
                    405 => 'Метод не поддерживается.',
                    429 => 'Слишком много запросов. Попробуйте через минуту.',
                    default => $e->getMessage() ?: 'Ошибка запроса.',
                };

                return response()->json(['message' => $message], $status, $e->getHeaders());
            }
        });

        // Непредвиденная ошибка: без стека и SQL при APP_DEBUG=false
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') && ! config('app.debug')) {
                return response()->json(['message' => 'Внутренняя ошибка сервера. Попробуйте позже.'], 500);
            }
        });
    })->create();
