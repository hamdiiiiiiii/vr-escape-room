<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_cannot_change_their_role_via_profile_update(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $response = $this
            ->actingAs($student)
            ->patch('/profile', [
                'name' => 'Attempted Hacker',
                'email' => 'hacker@example.com',
                'role' => 'instructor',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $student->refresh();

        $this->assertSame('student', $student->role);
        $this->assertSame('Attempted Hacker', $student->name);
        $this->assertSame('hacker@example.com', $student->email);
    }

    public function test_profile_destroy_route_is_disabled(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        // Route is disabled, so DELETE method is not allowed or not found
        $this->assertTrue(in_array($response->status(), [404, 405]));
        $this->assertNotNull($user->fresh());
    }

    public function test_profile_page_does_not_render_delete_account_section(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
        $response->assertDontSee('Delete Account');
        $response->assertSee('Profile Settings');
        $response->assertSee('Profile Information');
        $response->assertSee('Update Password');
    }
}
