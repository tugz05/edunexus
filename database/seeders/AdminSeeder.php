<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin account
        User::updateOrCreate(
            ['email' => 'admin@edunexus.com'],
            [
                'name' => 'Admin User',
                'email' => 'admin@edunexus.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Admin account created successfully!');
        $this->command->info('Email: admin@edunexus.com');
        $this->command->info('Password: password');
        $this->command->warn('Please change the password after first login!');
    }
}
