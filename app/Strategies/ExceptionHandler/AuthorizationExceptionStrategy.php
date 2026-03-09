<?php

namespace App\Strategies\ExceptionHandler;

use App\Contracts\ExceptionHandlerInterface;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class AuthorizationExceptionStrategy implements ExceptionHandlerInterface
{
    private function isProduction(): bool
    {
        return config('app.env') === 'production';
    }

    /**
     * @param AccessDeniedHttpException $exception
     */
    public function render($exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'message' => "Access Denied",
                'reason' => !$this->isProduction()
                    ? $exception->getMessage()
                    : "No message available",
                'code' => $exception->getStatusCode(),
            ]
        ], $exception->getStatusCode());
    }
}
