<?php

namespace Tests\Feature;

use App\Support\Uploads;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Uploads is the one place that decides where an author's files live, so the
 * rules it owns are worth pinning down: external links are never touched,
 * stored files follow the configured disk, and a private S3 bucket gets
 * signed URLs rather than 403s.
 */
class UploadsTest extends TestCase
{
    public function test_an_external_link_is_never_treated_as_ours(): void
    {
        foreach (['http://contoh.test/a.png', 'https://contoh.test/a.png'] as $url) {
            $this->assertTrue(Uploads::isExternal($url));
            $this->assertFalse(Uploads::isStored($url));
            $this->assertSame($url, Uploads::url($url));
        }
    }

    public function test_a_stored_path_is_ours(): void
    {
        $this->assertTrue(Uploads::isStored('galeri/a.png'));
        $this->assertFalse(Uploads::isExternal('galeri/a.png'));
    }

    public function test_blank_values_are_neither(): void
    {
        foreach ([null, ''] as $blank) {
            $this->assertFalse(Uploads::isExternal($blank));
            $this->assertFalse(Uploads::isStored($blank));
            $this->assertNull(Uploads::url($blank));
        }
    }

    public function test_files_are_stored_on_the_configured_disk(): void
    {
        config(['uploads.disk' => 'uji']);
        Storage::fake('uji');

        $path = Uploads::store(UploadedFile::fake()->image('wajah.png'), 'portraits');

        $this->assertStringStartsWith('portraits/', $path);
        Storage::disk('uji')->assertExists($path);
    }

    public function test_delete_removes_stored_files_and_ignores_external_links(): void
    {
        config(['uploads.disk' => 'uji']);
        Storage::fake('uji');

        $path = Uploads::store(UploadedFile::fake()->image('peta.png'), 'maps');

        // A mixed batch is exactly what WorldController hands over when a
        // world is deleted: some uploads, some pasted URLs, some nulls.
        Uploads::delete([$path, 'https://contoh.test/luar.png', null]);

        Storage::disk('uji')->assertMissing($path);
    }

    public function test_delete_tolerates_a_bare_null(): void
    {
        config(['uploads.disk' => 'uji']);
        Storage::fake('uji');

        Uploads::delete(null);

        $this->assertTrue(true, 'tidak melempar galat');
    }

    public function test_local_disk_serves_plain_urls(): void
    {
        config(['uploads.disk' => 'public', 'uploads.signed' => null]);

        $this->assertFalse(Uploads::signsUrls());
        $this->assertStringContainsString('/storage/galeri/a.png', (string) Uploads::url('galeri/a.png'));
    }

    public function test_s3_is_signed_by_default_because_a_fresh_bucket_is_private(): void
    {
        config(['uploads.disk' => 's3', 'uploads.signed' => null]);

        $this->assertTrue(Uploads::signsUrls());
    }

    public function test_signing_can_be_turned_off_for_a_public_bucket(): void
    {
        config(['uploads.disk' => 's3', 'uploads.signed' => false]);

        $this->assertFalse(Uploads::signsUrls());
    }
}
