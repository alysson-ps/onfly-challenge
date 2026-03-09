<?php

namespace App\Strategies\ExceptionHandler;

use App\Contracts\ExceptionHandlerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

final class ValidationExceptionStrategy implements ExceptionHandlerInterface
{
    /**
     * @param ValidationException $exception
     */
    public function render($exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'message' => "Validation Error",
                'fields' => $exception->errors(),
                'code' => 422,
            ]
        ], 422);
    }
}
