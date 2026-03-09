<?php

namespace App\Factories;

use App\Exceptions\OrderCancellationException;
use App\Strategies\ExceptionHandler\AuthenticationExceptionStrategy;
use App\Strategies\ExceptionHandler\AuthorizationExceptionStrategy;
use App\Strategies\ExceptionHandler\ModelNotFoundExceptionStrategy;
use App\Strategies\ExceptionHandler\OrderCancellationExceptionStrategy;
use App\Strategies\ExceptionHandler\ValidationExceptionStrategy;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class ExceptionHandlerFactory
{
    private static $exceptions = [
        ValidationException::class => ValidationExceptionStrategy::class,
        AccessDeniedHttpException::class => AuthorizationExceptionStrategy::class,
        OrderCancellationException::class => OrderCancellationExceptionStrategy::class,
        NotFoundHttpException::class => ModelNotFoundExceptionStrategy::class,
        AuthenticationException::class => AuthenticationExceptionStrategy::class,
    ];

    private static function isProduction(): bool
    {
        return config('app.env') === 'production';
    }

    public static function make(Throwable $exception)
    {
        if (array_key_exists(get_class($exception), self::$exceptions)) {
            $strategy = self::$exceptions[get_class($exception)];

            if ($strategy) return app()
                ->make($strategy)
                ->render($exception);
        }

        return response()->json([
            'success' => false,
            'error' => [
                'message' => "Internal Server Error",
                'reason' => !self::isProduction()
                    ? $exception->getMessage()
                    : "No message available",
                'code' => 500,
            ]
        ], 500);
    }
}
