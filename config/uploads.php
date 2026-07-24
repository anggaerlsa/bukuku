<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disk for author uploads
    |--------------------------------------------------------------------------
    |
    | Every file an author uploads — character portraits, location maps, novel
    | and world covers, organisation emblems, lore covers and the gallery — is
    | written to this disk, deleted from it, and has its public URL read back
    | from it. One setting moves all of them together.
    |
    | It stays on the local `public` disk by default, so a fresh checkout works
    | with nothing configured. Set UPLOAD_DISK=s3 in .env (with the AWS_* keys)
    | to move uploads to S3; `php artisan uploads:pindah` carries over the
    | files that are already on the local disk.
    |
    | The folder names inside the disk (portraits/, galeri/, …) are unchanged,
    | so paths already stored in the database keep resolving.
    |
    */

    'disk' => env('UPLOAD_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Signed URLs
    |--------------------------------------------------------------------------
    |
    | A fresh S3 bucket denies public reads, so a plain object URL answers 403
    | and every picture in the app breaks. Rather than ask the author to open
    | their bucket to the whole internet, images are served through short-lived
    | signed URLs: the bucket stays private, and unpublished lore stays
    | unreadable to anyone without a link we just minted.
    |
    | Leave this null to decide automatically — sign on S3, don't on the local
    | disk. Set UPLOAD_SIGNED=false if you have instead made the bucket (or a
    | CloudFront distribution in front of it) publicly readable; plain URLs
    | cache far better in the browser.
    |
    | Signing is a local HMAC — it costs no API call.
    |
    */

    'signed' => env('UPLOAD_SIGNED', null),

    /** How long a signed URL stays valid, in minutes. */
    'signed_ttl' => (int) env('UPLOAD_SIGNED_TTL', 60),

];
