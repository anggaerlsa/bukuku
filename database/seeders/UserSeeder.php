<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The three starting accounts. Registration is disabled, so these are seeded
 * rather than signed up for.
 *
 * Addresses and passwords come from config/seed.php, which reads them from the
 * environment: keeping them out of the repository means a clone gets working
 * demo accounts without ever publishing real credentials. Override them in
 * .env (git-ignored) — see .env.example for the keys.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Superadmin — owner account.
        $superadmin = User::updateOrCreate(
            ['email' => config('seed.superadmin.email')],
            [
                'name' => 'Superadmin',
                'username' => 'superadmin',
                'password' => config('seed.superadmin.password'),
                'email_verified_at' => now(),
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
            ]
        );
        $author->syncRoles('author');
    }
}
