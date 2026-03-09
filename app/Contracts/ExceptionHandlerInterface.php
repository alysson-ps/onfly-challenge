<?php

namespace App\Contracts;

use Illuminate\Http\JsonResponse;

interface ExceptionHandlerInterface
{
    public function render($exception): JsonResponse;
}