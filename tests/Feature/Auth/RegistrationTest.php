<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Registration hands the new account the `author` role.
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_and_land_on_the_waiting_page(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('pending', absolute: false));

        $user = User::where('email', 'test@example.com')->sole();
        $this->assertSame('pending', $user->status);
        $this->assertTrue($user->hasRole('author'));
        $this->assertNull($user->approved_at);
    }

    public function test_a_pending_account_cannot_reach_the_app(): void
    {
        $user = User::factory()->pending()->create();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('pending', absolute: false));
        $this->actingAs($user)->get('/kelola/novel')->assertRedirect(route('pending', absolute: false));
    }

    public function test_a_rejected_account_cannot_reach_the_app(): void
    {
        $user = User::factory()->rejected()->create();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('pending', absolute: false));
    }

    public function test_an_approved_account_is_not_held_on_the_waiting_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('pending'))->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_username_and_email_must_be_unique(): void
    {
        User::factory()->create(['username' => 'taken', 'email' => 'taken@example.com']);

        $this->post('/register', [
            'name' => 'Test User',
            'username' => 'taken',
            'email' => 'taken@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors(['username', 'email']);

        $this->assertGuest();
    }
}
