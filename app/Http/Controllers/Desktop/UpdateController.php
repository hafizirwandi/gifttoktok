<?php

namespace App\Http\Controllers\Desktop;

use App\Http\Controllers\Controller;
use App\Services\Desktop\UpdateChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Update otomatis versi desktop: banner di layout (lihat
 * components/desktop-update-banner.blade.php) cek status() lalu POST install().
 */
class UpdateController extends Controller
{
    public function status(Request $request, UpdateChecker $checker): JsonResponse
    {
        return response()->json($checker->status($request->boolean('fresh')));
    }

    public function install(UpdateChecker $checker): JsonResponse
    {
        abort_unless(config('desktop.enabled') && PHP_OS_FAMILY === 'Windows', 404);

        $status = $checker->status(fresh: true);

        if (! $status['available']) {
            return response()->json(['message' => 'Sudah versi terbaru.'], 422);
        }

        // Download installer (~70MB) - built-in PHP server single-thread, jadi request
        // ini memang memblok sebentar. Setelah installer jalan, ia menghentikan server
        // ini sendiri (launcher -Stop) lalu membuka aplikasi lagi setelah selesai.
        set_time_limit(0);

        $dir = config('desktop.data_path').DIRECTORY_SEPARATOR.'run'.DIRECTORY_SEPARATOR.'updates';
        File::ensureDirectoryExists($dir);
        File::cleanDirectory($dir);
        $setup = $dir.DIRECTORY_SEPARATOR."GiftTokTok-Setup-{$status['latest']}.exe";

        try {
            Http::withUserAgent('GiftTokTok-Desktop-Updater')
                ->timeout(600)
                ->sink($setup)
                ->get($status['download_url'])
                ->throw();
        } catch (Throwable $e) {
            Log::warning('Download update desktop gagal: '.$e->getMessage());
            File::delete($setup);

            return response()->json(['message' => 'Gagal mengunduh update. Cek koneksi internet lalu coba lagi.'], 502);
        }

        if ($status['sha256'] && ! hash_equals($status['sha256'], hash_file('sha256', $setup))) {
            File::delete($setup);

            return response()->json(['message' => 'File update rusak (checksum tidak cocok). Coba lagi.'], 502);
        }

        // `start ""` lewat cmd -> installer TIDAK jadi child process PHP ini, jadi
        // tetap hidup waktu installer mematikan web server (php.exe) sebelum menimpa file.
        pclose(popen('start "" "'.$setup.'" /VERYSILENT /SUPPRESSMSGBOXES /NORESTART', 'r'));

        return response()->json([
            'message' => "Update ke versi {$status['latest']} sedang dipasang. GiftTokTok akan terbuka lagi otomatis dalam 1-2 menit.",
        ]);
    }
}
