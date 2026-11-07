<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeedDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_instructor_and_five_students(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 6);

        // Verify instructor
        $instructor = User::where('email', 'instructor@test.com')->first();
        $this->assertNotNull($instructor);
        $this->assertEquals('instructor', $instructor->role);
        $this->assertTrue($instructor->isInstructor());
        $this->assertFalse($instructor->isStudent());
        $this->assertNotNull($instructor->email_verified_at);
        $this->assertTrue(Hash::check('password', $instructor->password));

        // Verify 5 students
        for ($i = 1; $i <= 5; $i++) {
            $student = User::where('email', "student{$i}@test.com")->first();
            $this->assertNotNull($student);
            $this->assertEquals('student', $student->role);
            $this->assertTrue($student->isStudent());
            $this->assertFalse($student->isInstructor());
            $this->assertNotNull($student->email_verified_at);
            $this->assertTrue(Hash::check('password', $student->password));
        }
    }

    public function test_all_seeded_users_can_authenticate_via_login_endpoint(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Instructor login
        $response = $this->post('/login', [
            'email' => 'instructor@test.com',
            'password' => 'password',
        ]);
        $this->assertAuthenticated();
        $this->post('/logout');
        $this->assertGuest();

        // Each student login
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->post('/login', [
                'email' => "student{$i}@test.com",
                'password' => 'password',
            ]);
            $this->assertAuthenticated();
            $this->post('/logout');
            $this->assertGuest();
        }
    }
}

