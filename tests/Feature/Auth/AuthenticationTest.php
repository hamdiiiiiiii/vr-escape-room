<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_student_is_redirected_to_student_dashboard_after_login(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $response = $this->post('/login', [
            'email' => $student->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('student.dashboard', absolute: false));
    }

    public function test_instructor_is_redirected_to_instructor_dashboard_after_login(): void
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
        ]);

        $response = $this->post('/login', [
            'email' => $instructor->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('instructor.dashboard', absolute: false));
    }

    public function test_dashboard_route_redirects_to_student_dashboard_for_student(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertRedirect(route('student.dashboard'));
    }

    public function test_dashboard_route_redirects_to_instructor_dashboard_for_instructor(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        $response = $this->actingAs($instructor)->get('/dashboard');

        $response->assertRedirect(route('instructor.dashboard'));
    }

    public function test_users_can_not_authenticate_with_invalid_password_and_error_is_returned(): void
    {
        $user = User::factory()->create();

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email' => trans('auth.failed')]);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
