<?php

namespace App\Console\Commands;

use App\Support\Uploads;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Carry files that are already on one disk over to another — in practice,
 * the local `public` disk to S3 the first time UPLOAD_DISK is switched.
 *
 * Paths are kept byte-for-byte identical (`galeri/abc.png` stays
 * `galeri/abc.png`), so every value already stored in the database keeps
 * resolving and nothing has to be rewritten.
 *
 * It copies, and only deletes the source when asked to. Running it twice is
 * harmless: a file already at the destination is skipped unless --timpa.
 */
class PindahkanUploads extends Command
{
    protected $signature = 'uploads:pindah
        {--dari=public : Disk asal}
        {--ke= : Disk tujuan (bawaan: config uploads.disk)}
        {--timpa : Salin ulang berkas yang sudah ada di tujuan}
        {--hapus-asal : Hapus berkas di disk asal setelah tersalin}
        {--coba : Hanya tampilkan rencananya, jangan menyalin apa pun}';

    protected $description = 'Salin berkas unggahan dari satu disk ke disk lain (mis. public → s3)';

    /**
     * The .env keys an S3 disk still needs, or [] if it is fine (or not S3).
     *
     * @return list<string>
     */
    private function missingS3Keys(string $disk): array
    {
        $config = config("filesystems.disks.{$disk}", []);

        if (($config['driver'] ?? null) !== 's3') {
            return [];
        }

        $envFor = [
            'key' => 'AWS_ACCESS_KEY_ID',
            'secret' => 'AWS_SECRET_ACCESS_KEY',
            'region' => 'AWS_DEFAULT_REGION',
            'bucket' => 'AWS_BUCKET',
        ];

        return collect($envFor)
            ->reject(fn (string $env, string $key) => filled($config[$key] ?? null))
            ->values()
            ->all();
    }

    public function handle(): int
    {
        $from = (string) $this->option('dari');
        $to = (string) ($this->option('ke') ?: Uploads::diskName());
        $dryRun = (bool) $this->option('coba');

        if ($from === $to) {
            $this->error("Disk asal dan tujuan sama-sama \"{$from}\" — tidak ada yang perlu dipindah.");

            return self::FAILURE;
        }

        foreach ([$from, $to] as $disk) {
            if (! config("filesystems.disks.{$disk}")) {
                $this->error("Disk \"{$disk}\" tidak ada di config/filesystems.php.");

                return self::FAILURE;
            }
        }

        // Fail loudly and early on a half-configured S3 disk. Checking the
        // config is the only reliable way: the s3 disk ships with
        // `throw => false`, so reading from it without credentials returns an
        // empty list instead of raising anything.
        foreach ([$from, $to] as $disk) {
            if ($missing = $this->missingS3Keys($disk)) {
                $this->error("Disk \"{$disk}\" belum lengkap konfigurasinya.");
                $this->line('Isi dulu di .env: ' . implode(', ', $missing));

                return self::FAILURE;
            }
        }

        $source = Storage::disk($from);
        $target = Storage::disk($to);

        // Housekeeping files Laravel puts in storage/app/public are not
        // uploads and have no business on S3.
        $files = collect($source->allFiles())
            ->reject(fn (string $path) => str_starts_with(basename($path), '.'))
            ->values()
            ->all();

        if (! $files) {
            $this->info("Tidak ada berkas di disk \"{$from}\".");

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s %d berkas: %s → %s',
            $dryRun ? '[COBA] akan menyalin' : 'Menyalin',
            count($files),
            $from,
            $to
        ));

        $copied = $skipped = $failed = 0;

        foreach ($files as $path) {
            if (! $this->option('timpa') && $target->exists($path)) {
                $this->line("  lewati (sudah ada)  {$path}");
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line("  salin               {$path}");
                $copied++;

                continue;
            }

            try {
                $stream = $source->readStream($path);

                if ($stream === null) {
                    throw new \RuntimeException('tidak terbaca');
                }

                $target->writeStream($path, $stream);

                if (is_resource($stream)) {
                    fclose($stream);
                }

                // Only now is it safe to let go of the original.
                if ($this->option('hapus-asal')) {
                    $source->delete($path);
                }

                $this->line("  ok                  {$path}");
                $copied++;
            } catch (\Throwable $e) {
                $this->line("  <fg=red>GAGAL</>               {$path} — {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Selesai: {$copied} disalin, {$skipped} dilewati, {$failed} gagal.");

        if (! $dryRun && $copied > 0) {
            $this->line("Pastikan UPLOAD_DISK={$to} di .env agar unggahan berikutnya ikut ke sana.");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
