<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A fresh install has to be usable by the accounts it ships with.
 *
 * `users.status` defaults to `pending`, so a seeder that forgets to set it
 * puts the superadmin behind the approval gate that only a superadmin can
 * open — nobody could ever sign in again. That happened; this keeps it fixed.
 */
class SeededAccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(UserSeeder::class);
    }

    public function test_every_seeded_account_is_approved(): void
    {
        $this->assertSame(3, User::count());

        foreach (User::all() as $user) {
            $this->assertSame('active', $user->status, "akun {$user->username} tidak aktif");
            $this->assertNotNull($user->approved_at, "akun {$user->username} tanpa approved_at");
        }
    }

    public function test_the_seeded_superadmin_can_reach_the_app_and_approve(): void
    {
        $superadmin = User::where('username', 'superadmin')->sole();

        $this->actingAs($superadmin)->get('/dashboard')->assertOk();
        $this->actingAs($superadmin)->get(route('users.index'))->assertOk();

        // The whole point of the account: it can let the first signup in.
        $pending = User::factory()->pending()->create();

        $this->actingAs($superadmin)
            ->patch(route('users.approve', $pending), ['decision' => 'approve'])
            ->assertRedirect();

        $this->assertSame('active', $pending->refresh()->status);
    }

    public function test_no_seeded_account_carries_a_real_persons_details(): void
    {
        // This repository is public: placeholders and seeds stay generic.
        foreach (User::all() as $user) {
            $this->assertStringEndsWith('@bukuku.test', $user->email);
            $this->assertContains($user->username, ['superadmin', 'admin', 'author']);
        }
    }
}
