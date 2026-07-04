<?php

declare(strict_types=1);

use App\Exceptions\InvalidStatusTransitionException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Force JSON rendering for all api/* requests.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson(),
        );

        // 404 — model not found or route not found → envelope-compliant response.
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'data'    => null,
                    'message' => 'Resource not found.',
                    'errors'  => null,
                ], 404);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'data'    => null,
                    'message' => 'Resource not found.',
                    'errors'  => null,
                ], 404);
            }
        });

        // 401 — unauthenticated → envelope-compliant response.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'data'    => null,
                    'message' => 'Unauthenticated.',
                    'errors'  => null,
                ], 401);
            }
        });

        // 403 — policy/authorization denial → envelope-compliant response.
        // Laravel converts AuthorizationException to AccessDeniedHttpException
        // (via Handler::prepareException) before render callbacks run, so the
        // callback must be typed on the converted exception, not the original.
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'data'    => null,
                    'message' => $e->getMessage() ?: 'This action is unauthorized.',
                    'errors'  => null,
                ], 403);
            }
        });

        // 422 — invalid purchase request status transition.
        $exceptions->render(function (InvalidStatusTransitionException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'data'    => null,
                    'message' => $e->getMessage(),
                    'errors'  => null,
                ], 422);
            }
        });

        // 422 — validation failure → envelope-compliant response with field-level errors.
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'data'    => null,
                    'message' => 'Validation failed.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        // 500 — catch-all for unexpected exceptions in production.
        // In non-production, let Laravel's default handler expose the exception detail.
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') && app()->isProduction()) {
                return response()->json([
                    'data'    => null,
                    'message' => 'An unexpected error occurred.',
                    'errors'  => null,
                ], 500);
            }
        });
    })->create();
