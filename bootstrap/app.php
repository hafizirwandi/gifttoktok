<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

// Mode desktop (installer Windows): launcher (installer/launcher/GiftTokTok.ps1) set
// env GIFTTOKTOK_DATA ke folder data per-user (%APPDATA%\GiftTokTok). .env, database
// SQLite & storage/ (upload, cache, log, session) dibaca dari SITU, bukan dari folder
// aplikasi - jadi update versi (installer menimpa folder aplikasi) tidak menyentuh
// data user sama sekali. Tanpa env ini (development biasa) semuanya tetap default.
if ($desktopDataPath = getenv('GIFTTOKTOK_DATA')) {
    $app->useEnvironmentPath($desktopDataPath);
    $app->useStoragePath($desktopDataPath.DIRECTORY_SEPARATOR.'storage');
}

return $app;
