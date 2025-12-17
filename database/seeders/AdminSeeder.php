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
        User::updateOrCreate(
            ['phone' => '01000000000'],
            [
                'name' => 'Admin',
                'phone' => '01000000000',
                'password' => Hash::make('password123'),
                'is_admin' => true,
                'verified' => true,
                'completed' => true,
            ]
        );

        $this->command->info('Admin user created successfully!');
        $this->command->info('Phone: 01000000000');
        $this->command->info('Password: password123');
    }
}
