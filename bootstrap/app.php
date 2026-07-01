<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Channex posts the booking webhook server-to-server (no CSRF token);
        // it is authenticated by a shared-secret header in the controller.
        $middleware->validateCsrfTokens(except: ['channex/webhook']);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Retention: channel sync audit (90d) + website search demand log (2y).
        $schedule->command('model:prune', ['--model' => [\App\Models\ChannelSyncLog::class, \App\Models\WebsiteSearchLog::class]])->daily();
        // Catch-up: re-pull any OTA booking a missed webhook left unacknowledged.
        $schedule->command('channex:pull-bookings')->everyFifteenMinutes()->withoutOverlapping();
        // On-the-books snapshot per future date × room type (pickup-pace history).
        // Runs before the 04:00 ARI push so both see the same overnight state.
        $schedule->command('pricing:snapshot')->dailyAt('03:30');
        // Nightly safety-net: re-push availability + rates in case a real-time push was missed.
        $schedule->command('channex:push-ari --queue')->dailyAt('04:00');
    })
    ->create();
