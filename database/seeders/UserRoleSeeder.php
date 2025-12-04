<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a student account
        User::updateOrCreate(
            ['email' => 'student@edunexus.com'],
            [
                'name' => 'Student User',
                'email' => 'student@edunexus.com',
                'password' => Hash::make('password'),
                'role' => 'student',
                'email_verified_at' => now(),
            ]
        );

        // Create a teacher account
        User::updateOrCreate(
            ['email' => 'teacher@edunexus.com'],
            [
                'name' => 'Teacher User',
                'email' => 'teacher@edunexus.com',
                'password' => Hash::make('password'),
                'role' => 'teacher',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Student and Teacher accounts created successfully!');
        $this->command->info('Student: student@edunexus.com / password');
        $this->command->info('Teacher: teacher@edunexus.com / password');
    }
}
