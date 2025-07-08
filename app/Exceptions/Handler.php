<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Throwable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (Throwable $e, $request) {
            if ($request->expectsJson()) {

                // Handle validation exceptions
                if ($e instanceof ValidationException) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $e->errors(),
                    ], 422);
                }

                // Handle unauthenticated access
                if ($e instanceof AuthenticationException) {
                    return response()->json([
                        'message' => 'Unauthenticated.',
                    ], 401);
                }

                // Handle 404
                if ($e instanceof NotFoundHttpException) {
                    return response()->json([
                        'message' => 'Route not found.',
                    ], 404);
                }

                // Handle method not allowed
                if ($e instanceof MethodNotAllowedHttpException) {
                    return response()->json([
                        'message' => 'HTTP method not allowed.',
                    ], 405);
                }

                // Fallback for other unhandled exceptions
                return response()->json([
                    'message' => 'Server Error.',
                    'exception' => class_basename($e),
                    'error' => $e->getMessage(),
                ], 500);
            }
        });
    }
}
