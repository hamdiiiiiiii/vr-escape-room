<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('BugHunt VR');
        $response->assertSee('favicon.svg');
    }

    public function test_favicon_is_present_on_guest_and_app_layouts(): void
    {
        // Guest layout (login)
        $loginResponse = $this->get(route('login'));
        $loginResponse->assertStatus(200);
        $loginResponse->assertSee('favicon.svg');

        // App layout (student dashboard)
        $student = User::factory()->create(['role' => 'student']);
        $appResponse = $this->actingAs($student)->get(route('student.dashboard'));
        $appResponse->assertStatus(200);
        $appResponse->assertSee('favicon.svg');
        $appResponse->assertSee('BugHunt VR');
    }
}
