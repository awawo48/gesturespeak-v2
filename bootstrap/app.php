<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);

        // Children go to the student login, adults to the adult login
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->routeIs('learn.*', 'quiz.*', 'student.*')
                ? route('student.login')
                : route('login')
        );

        // Authenticated users visiting guest routes go to their respective dashboard/hub
        $middleware->redirectUsersTo(function (Request $request): string {
            if ($user = Auth::guard('web')->user()) {
                return route($user->homeRoute());
            }
            if (Auth::guard('student')->check()) {
                return route('learn.hub');
            }

            return route('home');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Prevent stale login/logout tabs from getting stuck on a dead "419 Page Expired" screen
        $exceptions->render(function (\Throwable $e, Request $request) {
            $isCsrfExpired = $e instanceof TokenMismatchException
                || ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 419);

            if ($isCsrfExpired && ! $request->expectsJson() && ! $request->is('api/*')) {
                if ($request->hasSession()) {
                    $request->session()->regenerateToken();
                }

                $fallbackUrl = $request->is('student/*') ? route('student.login') : route('login');
                $targetUrl = url()->previous() ?: $fallbackUrl;

                return redirect()->to($targetUrl)
                    ->withInput($request->except(['password', 'password_confirmation', 'pin', '_token']))
                    ->with('error', 'Sesi halaman telah tamat tempoh dan diperbaharui. Sila tekan Log Masuk semula.');
            }

            return null;
        });
    })->create();
