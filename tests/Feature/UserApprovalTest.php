<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Letting a new account in is the superadmin's call alone. An admin may see
 * the queue but must be refused by the server, not merely by a hidden button.
 */
class UserApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        return tap(User::factory()->create(), fn (User $u) => $u->syncRoles([$role]));
    }

    public function test_superadmin_can_approve_a_pending_account(): void
    {
        $superadmin = $this->userWithRole('superadmin');
        $pending = User::factory()->pending()->create();

        $this->actingAs($superadmin)
            ->patch(route('users.approve', $pending), ['decision' => 'approve'])
            ->assertRedirect();

        $pending->refresh();
        $this->assertSame('active', $pending->status);
        $this->assertSame($superadmin->id, $pending->approved_by);
        $this->assertNotNull($pending->approved_at);

        // And the gate actually opens.
        $this->actingAs($pending)->get('/dashboard')->assertOk();
    }

    public function test_superadmin_can_reject_a_pending_account(): void
    {
        $superadmin = $this->userWithRole('superadmin');
        $pending = User::factory()->pending()->create();

        $this->actingAs($superadmin)
            ->patch(route('users.approve', $pending), ['decision' => 'reject'])
            ->assertRedirect();

        $pending->refresh();
        $this->assertSame('rejected', $pending->status);
        $this->assertNull($pending->approved_at);

        $this->actingAs($pending)->get('/dashboard')->assertRedirect(route('pending', absolute: false));
    }

    public function test_admin_may_see_the_queue_but_cannot_approve(): void
    {
        $admin = $this->userWithRole('admin');
        $pending = User::factory()->pending()->create();

        $this->actingAs($admin)->get(route('users.index'))->assertOk();

        $this->actingAs($admin)
            ->patch(route('users.approve', $pending), ['decision' => 'approve'])
            ->assertForbidden();

        $this->assertSame('pending', $pending->refresh()->status);
    }

    public function test_author_cannot_approve(): void
    {
        $author = $this->userWithRole('author');
        $pending = User::factory()->pending()->create();

        $this->actingAs($author)
            ->patch(route('users.approve', $pending), ['decision' => 'approve'])
            ->assertForbidden();

        $this->assertSame('pending', $pending->refresh()->status);
    }

    public function test_a_pending_account_cannot_approve_itself(): void
    {
        $pending = User::factory()->pending()->create();

        $this->actingAs($pending)
            ->patch(route('users.approve', $pending), ['decision' => 'approve'])
            ->assertRedirect(route('pending', absolute: false));

        $this->assertSame('pending', $pending->refresh()->status);
    }

    public function test_decision_must_be_approve_or_reject(): void
    {
        $superadmin = $this->userWithRole('superadmin');
        $pending = User::factory()->pending()->create();

        $this->actingAs($superadmin)
            ->patch(route('users.approve', $pending), ['decision' => 'menghapus'])
            ->assertSessionHasErrors('decision');

        $this->assertSame('pending', $pending->refresh()->status);
    }

    public function test_an_account_created_by_an_admin_skips_the_queue(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Dibuat Admin',
            'username' => 'dibuatadmin',
            'email' => 'dibuat@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'author',
        ])->assertRedirect();

        $created = User::where('email', 'dibuat@example.com')->sole();
        $this->assertSame('active', $created->status);
        $this->assertSame($admin->id, $created->approved_by);
    }
}
