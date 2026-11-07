<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_student_dashboard(): void
    {
        $response = $this->get(route('student.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_from_instructor_dashboard(): void
    {
        $response = $this->get(route('instructor.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_student_can_access_student_dashboard(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Student Dashboard');
    }

    public function test_student_cannot_access_instructor_dashboard(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get(route('instructor.dashboard'));

        $response->assertStatus(403);
    }

    public function test_instructor_can_access_instructor_dashboard(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Instructor Dashboard');
    }

    public function test_instructor_cannot_access_student_dashboard(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        $response = $this->actingAs($instructor)->get(route('student.dashboard'));

        $response->assertStatus(403);
    }
}

