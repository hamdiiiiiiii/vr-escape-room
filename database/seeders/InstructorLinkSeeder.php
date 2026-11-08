<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class InstructorLinkSeeder extends Seeder
{
    public function run(): void
    {
        $instructor = User::where('email', 'instructor@test.com')->first();

        if (! $instructor) {
            return;
        }

        User::where('role', 'student')->update(['instructor_id' => $instructor->id]);
    }
}