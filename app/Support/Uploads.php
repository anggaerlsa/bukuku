<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The single place that decides WHERE an author's uploads live.
 *
 * Every picture in the app — character portraits, location maps, novel and
 * world covers, organisation emblems, lore covers, gallery images — is
 * written, deleted and linked through here, so moving them all to S3 is one
 * line in .env (`UPLOAD_DISK=s3`) rather than a hunt through the controllers.
 *
 * Each of those columns holds EITHER a path on that disk OR an external
 * http(s) URL the author pasted. An external link is not ours: never stored,
 * never deleted, and returned untouched as its own URL. That rule was
 * repeated in a dozen places before it moved in here.
 */
class Uploads
{
    /** The disk uploads live on, per config/uploads.php. */
    public static function disk(): Filesystem
    {
        return Storage::disk(static::diskName());
    }

    public static function diskName(): string
    {
        return (string) config('uploads.disk', 'public');
    }

    /** A link to somebody else's server — ours to show, not to manage. */
    public static function isExternal(?string $path): bool
    {
        return filled($path) && Str::startsWith($path, ['http://', 'https://']);
    }

    /** A file we actually put on the disk, so ours to move or delete. */
    public static function isStored(?string $path): bool
    {
        return filled($path) && ! static::isExternal($path);
    }

    /** Put an uploaded file on the disk and return its stored path. */
    public static function store(UploadedFile $file, string $folder): string
    {
        return $file->store($folder, static::diskName());
    }

    /**
     * Delete one path or many, ignoring nulls and external links so callers
     * can hand over a raw column value without filtering it first.
     */
    public static function delete(string|array|null $paths): void
    {
        $ours = collect(is_array($paths) ? $paths : [$paths])
            ->filter(fn ($path) => static::isStored($path))
            ->values()
            ->all();

        if ($ours) {
            static::disk()->delete($ours);
        }
    }

    /**
     * Whether stored files are served through short-lived signed URLs.
     *
     * Null in config means "decide from the disk": S3 buckets are private
     * until somebody opens them, so signing is the setting that works
     * without touching the bucket's permissions.
     */
    public static function signsUrls(): bool
    {
        $configured = config('uploads.signed');

        if ($configured !== null) {
            return (bool) $configured;
        }

        return config('filesystems.disks.' . static::diskName() . '.driver') === 's3';
    }

    /** The URL to show, whichever kind of value the column holds. */
    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (static::isExternal($path)) {
            return $path;
        }

        if (static::signsUrls()) {
            return static::disk()->temporaryUrl(
                $path,
                now()->addMinutes((int) config('uploads.signed_ttl', 60))
            );
        }

        return static::disk()->url($path);
    }
}
