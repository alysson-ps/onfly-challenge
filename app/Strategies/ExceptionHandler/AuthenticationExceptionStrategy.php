<?php

namespace App\Strategies\ExceptionHandler;

use App\Contracts\ExceptionHandlerInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;

final class AuthenticationExceptionStrategy implements ExceptionHandlerInterface
{
    /**
     * @param AuthenticationException $exception
     */
    public function render($exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'message' => "Token Expired or Invalid",
                'reason' => $exception->getMessage(),
                'code' => 401,
            ]
        ], 401);
    }
}
