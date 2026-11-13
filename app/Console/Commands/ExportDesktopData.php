<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Pindahkan data instalasi lama (MySQL + storage/app/public) ke format versi desktop
 * (SQLite). Hasilnya folder berisi database.sqlite + storage/app/public, yang di PC
 * tujuan cukup di-copy ke %APPDATA%\GiftTokTok\import lalu buka GiftTokTok - launcher
 * mengimpornya otomatis (lihat installer/README.md bagian "Pindah data").
 */
class ExportDesktopData extends Command
{
    protected $signature = 'gifttoktok:export-desktop
        {--output= : Folder tujuan (default: storage/app/desktop-export)}';

    protected $description = 'Export database (MySQL dll) + file upload ke paket import versi desktop (SQLite)';

    private const TARGET = 'desktop_export';

    /** Data sementara - tidak perlu dibawa (cache permission basi, session login lama, antrean job). */
    private const SKIP_TABLES = ['migrations', 'cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs'];

    public function handle(): int
    {
        $source = DB::connection();

        if ($source->getDriverName() === 'sqlite') {
            $this->error('Koneksi database saat ini sudah SQLite - tidak ada yang perlu diexport.');

            return self::FAILURE;
        }

        $pending = $this->pendingMigrations();
        if ($pending !== []) {
            $this->error('Masih ada migration yang belum dijalankan di database sumber. Jalankan "php artisan migrate" dulu:');
            $this->line('  - '.implode(PHP_EOL.'  - ', $pending));

            return self::FAILURE;
        }

        $output = $this->option('output') ?: storage_path('app/desktop-export');
        File::ensureDirectoryExists($output);
        $sqlitePath = $output.DIRECTORY_SEPARATOR.'database.sqlite';
        File::delete($sqlitePath);
        touch($sqlitePath);

        config(['database.connections.'.self::TARGET => [
            'driver' => 'sqlite',
            'database' => $sqlitePath,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);

        $this->info('Membuat struktur tabel SQLite...');
        Artisan::call('migrate', ['--database' => self::TARGET, '--force' => true]);

        $target = DB::connection(self::TARGET);
        $target->statement('PRAGMA foreign_keys = OFF');

        $tables = collect(Schema::connection(self::TARGET)->getTableListing(schemaQualified: false))
            ->reject(fn (string $table) => in_array($table, self::SKIP_TABLES, true) || str_starts_with($table, 'sqlite_'));

        foreach ($tables as $table) {
            if (! $source->getSchemaBuilder()->hasTable($table)) {
                $this->warn("  {$table}: tidak ada di database sumber, dilewati.");

                continue;
            }

            $columns = array_values(array_intersect(
                $source->getSchemaBuilder()->getColumnListing($table),
                Schema::connection(self::TARGET)->getColumnListing($table),
            ));

            // Kosongkan dulu baris bawaan migration (mis. data default) supaya tidak bentrok.
            $target->table($table)->delete();

            // cursor() (bukan chunk() ber-OFFSET) - tabel pivot tanpa kolom id tidak
            // punya urutan unik, chunk() bisa melewatkan/menggandakan baris.
            // Batch dibatasi jumlah kolom supaya tidak melewati limit parameter SQLite.
            $batchSize = max(1, intdiv(10000, count($columns)));
            $count = 0;

            $target->transaction(function () use ($source, $target, $table, $columns, $batchSize, &$count) {
                $batch = [];
                foreach ($source->table($table)->select($columns)->cursor() as $row) {
                    $batch[] = (array) $row;
                    if (count($batch) >= $batchSize) {
                        $target->table($table)->insert($batch);
                        $count += count($batch);
                        $batch = [];
                    }
                }
                if ($batch !== []) {
                    $target->table($table)->insert($batch);
                    $count += count($batch);
                }
            });

            $this->line("  {$table}: {$count} baris");
        }

        $target->statement('PRAGMA foreign_keys = ON');
        $violations = $target->select('PRAGMA foreign_key_check');
        if ($violations !== []) {
            $this->warn(count($violations).' baris punya foreign key yatim (data lama yang sudah tidak konsisten) - tetap diexport.');
        }

        $this->info('Menyalin file upload (storage/app/public)...');
        $publicStorage = storage_path('app/public');
        if (is_dir($publicStorage)) {
            File::copyDirectory($publicStorage, $output.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'public');
        }

        $this->newLine();
        $this->info("Selesai: {$output}");
        $this->line('Di PC tujuan: copy ISI folder ini ke %APPDATA%\\GiftTokTok\\import lalu buka GiftTokTok.');

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function pendingMigrations(): array
    {
        $ran = DB::table('migrations')->pluck('migration')->all();

        return collect(File::files(database_path('migrations')))
            ->map(fn ($file) => $file->getFilenameWithoutExtension())
            ->reject(fn (string $name) => in_array($name, $ran, true))
            ->values()
            ->all();
    }
}
