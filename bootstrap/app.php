<?php

use App\Exceptions\BaseException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle custom BaseException
        $exceptions->render(function (BaseException $e, Request $request): ?JsonResponse {
            // Log exception with context
            Log::error('Custom exception occurred', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
                'status_code' => $e->getStatusCode(),
                'context' => $e->getContext(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(
                    $e->toArray(),
                    $e->getStatusCode()
                );
            }

            return null;
        });

        // Handle ValidationException
        $exceptions->render(function (ValidationException $e, Request $request): ?JsonResponse {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Validation failed',
                    'code' => 'VALIDATION_ERROR',
                    'errors' => $e->errors(),
                ], 422);
            }

            return null;
        });

        // Handle ModelNotFoundException
        $exceptions->render(function (ModelNotFoundException $e, Request $request): ?JsonResponse {
            if ($request->expectsJson() || $request->is('api/*')) {
                Log::warning('Model not found', [
                    'model' => $e->getModel(),
                    'ids' => $e->getIds(),
                ]);

                return response()->json([
                    'message' => 'Resource not found',
                    'code' => 'RESOURCE_NOT_FOUND',
                    'errors' => [],
                ], 404);
            }

            return null;
        });

        // Handle NotFoundHttpException
        $exceptions->render(function (NotFoundHttpException $e, Request $request): ?JsonResponse {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Route not found',
                    'code' => 'ROUTE_NOT_FOUND',
                    'errors' => [],
                ], 404);
            }

            return null;
        });

        // Handle all other exceptions
        $exceptions->render(function (\Throwable $e, Request $request): ?JsonResponse {
            // Log unexpected exceptions
            Log::error('Unexpected exception occurred', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson() || $request->is('api/*')) {
                $statusCode = 500;
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                    $statusCode = $e->getStatusCode();
                }

                return response()->json([
                    'message' => app()->environment('production')
                        ? 'Internal server error'
                        : $e->getMessage(),
                    'code' => 'INTERNAL_ERROR',
                    'errors' => [],
                ], $statusCode);
            }

            return null;
        });
    })->create();
