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

            // Laravel cek APP_ENV di level OS (getenv, SEBELUM .env dibaca sama sekali)
            // - kalau ada, dia load `.env.{APP_ENV}` MENGGANTIKAN `.env` sepenuhnya (lihat
            // Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::checkForSpecificEnvironmentFile()).
            // Sudah TERBUKTI BUKAN penyebabnya (app_env_from_os_getenv selalu null di kasus
            // nyata) - dibiarkan di log ini cuma buat definitif menutup kemungkinan itu tiap
            // kali, bukan tebakan lagi.
            $osAppEnv = \Illuminate\Support\Env::get('APP_ENV');
            $envSpecificPath = $osAppEnv ? base_path('.env.'.$osAppEnv) : null;

            // db_connection_env_raw sempat balik NULL walau .env ADA dan APP_KEY/DB_CONNECTION
            // diyakini sudah ditulis di dalamnya - satu-satunya penjelasan yang konsisten:
            // Dotenv::safeLoad() gagal parse SELURUH file (bukan cuma baris yang salah) dan
            // diam-diam skip semuanya, biasanya karena file disimpan bukan UTF-8 murni (mis.
            // Notepad Windows nulis BOM UTF-8/UTF-16 di awal file) atau ada syntax error (kutip
            // tidak ditutup, dst). Baris di bawah ini coba parse ULANG file-nya secara eksplisit
            // (bukan lewat safeLoad yang diam-diam nelan errornya) supaya pesan error PARSE-nya
            // sendiri (posisi baris, dsb) ketangkap di log - bukti langsung, bukan dugaan lagi.
            $envParseError = null;
            $envHasBom = null;

            if (file_exists($envPath)) {
                $rawEnv = file_get_contents($envPath);
                $envHasBom = str_starts_with($rawEnv, "\xEF\xBB\xBF") ? 'UTF-8 BOM'
                    : (str_starts_with($rawEnv, "\xFF\xFE") ? 'UTF-16 LE BOM'
                    : (str_starts_with($rawEnv, "\xFE\xFF") ? 'UTF-16 BE BOM' : false));

                try {
                    (new \Dotenv\Parser\Parser)->parse($rawEnv);
                } catch (\Dotenv\Exception\InvalidFileException $parseException) {
                    $envParseError = $parseException->getMessage();
                }
            }

            Log::channel('diagnostics')->error('Uncaught exception', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'app_environment_resolved' => app()->environment(),
                'app_env_from_os_getenv' => $osAppEnv,
                'environment_file_actually_loaded' => app()->environmentFile(),
                'env_production_style_file_exists' => $envSpecificPath ? file_exists($envSpecificPath) : null,
                'db_connection_resolved' => config('database.default'),
                'db_connection_env_raw' => env('DB_CONNECTION'),
                'env_file_parse_error' => $envParseError,
                'env_file_encoding_issue' => $envHasBom,
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
