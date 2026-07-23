<?php

/*
|--------------------------------------------------------------------------
| Seeded accounts
|--------------------------------------------------------------------------
|
| Registration is disabled, so the starting accounts are seeded instead.
| Their addresses and passwords are read from the environment so real
| credentials never live in the repository — set them in .env, which is
| git-ignored. The defaults below are safe demo values for a fresh clone.
|
| Read through config (not env() directly in the seeder) so the values
| survive `php artisan config:cache`.
|
*/

return [

    'superadmin' => [
        'email' => env('SEED_SUPERADMIN_EMAIL', 'superadmin@bukuku.test'),
        'password' => env('SEED_SUPERADMIN_PASSWORD', 'password'),
    ],

    'admin' => [
        'name' => env('SEED_ADMIN_NAME', 'Admin'),
        'username' => env('SEED_ADMIN_USERNAME', 'admin'),
        'email' => env('SEED_ADMIN_EMAIL', 'admin@bukuku.test'),
        'password' => env('SEED_ADMIN_PASSWORD', 'admin123'),
    ],

    'author' => [
        'email' => env('SEED_AUTHOR_EMAIL', 'author@bukuku.test'),
        'password' => env('SEED_AUTHOR_PASSWORD', 'password'),
    ],

];
