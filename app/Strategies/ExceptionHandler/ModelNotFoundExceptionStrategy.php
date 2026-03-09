<?php

namespace App\Strategies\ExceptionHandler;

use App\Contracts\ExceptionHandlerInterface;
use Illuminate\Http\JsonResponse;

final class ModelNotFoundExceptionStrategy implements ExceptionHandlerInterface
{
    /**
     * @param \Illuminate\Database\Eloquent\ModelNotFoundException $exception
     */
    public function render($exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'message' => "Record Not Found",
                'reason' => $exception->getMessage(),
                'code' => 404,
            ]
        ], 404);
    }   
}
