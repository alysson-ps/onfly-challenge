<?php

namespace App\Strategies\ExceptionHandler;

use App\Contracts\ExceptionHandlerInterface;
use App\Exceptions\OrderCancellationException;
use Illuminate\Http\JsonResponse;

final class OrderCancellationExceptionStrategy implements ExceptionHandlerInterface
{
    /**
     * @param OrderCancellationException $exception
     */
    public function render($exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'message' => "Order Cancellation Error",
                'reason' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ]
        ], $exception->getCode());
    }
}
