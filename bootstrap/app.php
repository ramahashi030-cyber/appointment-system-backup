<?php

use App\Http\Middleware\AuthenticateDoctor;
use App\Http\Middleware\EnsureAdminRole;
use App\Http\Middleware\EnsurePatientIsActive;
use App\Http\Middleware\EnsureTriagerRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->redirectGuestsTo(fn (): string => route('auth.login'));

        $middleware->alias([
            'doctor.auth' => AuthenticateDoctor::class,
            'admin.role' => EnsureAdminRole::class,
            'triager.role' => EnsureTriagerRole::class,
            'patient.active' => EnsurePatientIsActive::class,
        ]);
    })
        
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // An expired session on a logout button should just land on the login page,
        // not on a "419 Page Expired" screen.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419
                && ! $request->expectsJson()
                && $request->is('admin/logout', 'doctor/logout')) {
                return redirect()
                    ->route('auth.login')
                    ->with('status', 'You have been signed out.');
            }
        });
    })->create();
