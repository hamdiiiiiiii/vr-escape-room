<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function test_is_instructor_returns_true_for_instructor(): void
    {
        $user = new User(['role' => 'instructor']);
        $this->assertTrue($user->isInstructor());
        $this->assertFalse($user->isStudent());
    }

    public function test_is_student_returns_true_for_student(): void
    {
        $user = new User(['role' => 'student']);
        $this->assertTrue($user->isStudent());
        $this->assertFalse($user->isInstructor());
    }
}
