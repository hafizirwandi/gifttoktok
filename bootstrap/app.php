<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Snapshot config yang BENAR-BENAR aktif saat exception terjadi - dibuat
        // khusus setelah kasus "500 SQLite padahal pakai MySQL" yang penyebabnya
        // bootstrap/cache/config.php basi (config:cache lama dipakai terus walau
        // .env sudah benar) dan sempat tidak hilang walau sudah config:clear/
        // optimize:clear di satu komputer. Pesan exception-nya sendiri (mis.
        // "database.sqlite does not exist") tidak menyebutkan config cache sama
        // sekali - baris di bawah ini yang membuktikan penyebabnya, bukan tebakan.
        $exceptions->reportable(function (Throwable $e) {
            $configCachePath = base_path('bootstrap/cache/config.php');
            $envPath = base_path('.env');

            Log::channel('diagnostics')->error('Uncaught exception', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'db_connection_resolved' => config('database.default'),
                'db_connection_env_raw' => env('DB_CONNECTION'),
                'config_cache_exists' => file_exists($configCachePath),
                'config_cache_mtime' => file_exists($configCachePath)
                    ? date('Y-m-d H:i:s', filemtime($configCachePath))
                    : null,
                'env_file_exists' => file_exists($envPath),
                'env_file_mtime' => file_exists($envPath)
                    ? date('Y-m-d H:i:s', filemtime($envPath))
                    : null,
                'php_sapi' => PHP_SAPI,
            ]);
        });
    })->create();
