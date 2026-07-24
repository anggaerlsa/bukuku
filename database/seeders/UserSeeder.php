<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The three accounts a fresh install starts with — someone has to be able to
 * approve the first registration, and an approver cannot register themselves.
 *
 * Addresses and passwords come from config/seed.php, which reads them from the
 * environment: keeping them out of the repository means a clone gets working
 * demo accounts without ever publishing real credentials. Override them in
 * .env (git-ignored) — see .env.example for the keys.
 *
 * Every value here stays generic on purpose. No real name, username or
 * address belongs in a seeder or a placeholder — this repository is public.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // These accounts predate the approval queue and must never sit in it.
        // `users.status` defaults to `pending`, so leaving it unset would put
        // the superadmin behind the very gate only a superadmin can open —
        // a fresh install would lock everyone out for good.
        $disetujui = [
            'status' => 'active',
            'approved_at' => now(),
        ];

        // Superadmin — owner account.
        $superadmin = User::updateOrCreate(
            ['email' => config('seed.superadmin.email')],
            [
                'name' => 'Superadmin',
                'username' => 'superadmin',
                'password' => config('seed.superadmin.password'),
                'email_verified_at' => now(),
                ...$disetujui,
            ]
        );
        $superadmin->syncRoles('superadmin');

        // Admin.
        $admin = User::updateOrCreate(
            ['email' => config('seed.admin.email')],
            [
                'name' => config('seed.admin.name'),
                'username' => config('seed.admin.username'),
                'password' => config('seed.admin.password'),
                'email_verified_at' => now(),
                ...$disetujui,
            ]
        );
        $admin->syncRoles('admin');

        // Author — demo writer account.
        $author = User::updateOrCreate(
            ['email' => config('seed.author.email')],
            [
                'name' => 'Penulis Contoh',
                'username' => 'author',
                'password' => config('seed.author.password'),
                'email_verified_at' => now(),
                ...$disetujui,
            ]
        );
        $author->syncRoles('author');
    }
}
