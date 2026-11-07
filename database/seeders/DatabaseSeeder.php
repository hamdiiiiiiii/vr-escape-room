<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1 instructor
        User::updateOrCreate(
            ['email' => 'instructor@test.com'],
            [
                'name' => 'Instructor User',
                'password' => Hash::make('password'),
                'role' => 'instructor',
                'email_verified_at' => now(),
            ]
        );

        // 5 students
        for ($i = 1; $i <= 5; $i++) {
            User::updateOrCreate(
                ['email' => "student{$i}@test.com"],
                [
                    'name' => "Student {$i}",
                    'password' => Hash::make('password'),
                    'role' => 'student',
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
