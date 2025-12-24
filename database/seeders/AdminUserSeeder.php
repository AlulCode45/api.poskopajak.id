<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@poskopajak.id'],
            [
                'name' => 'Admin PoskoPajak',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        // Assign admin role
        $admin->assignRole('admin');

        $this->command->info('Admin user created: admin@poskopajak.id / password');

        // Create moderator user
        $moderator = User::firstOrCreate(
            ['email' => 'moderator@poskopajak.id'],
            [
                'name' => 'Moderator PoskoPajak',
                'password' => Hash::make('password'),
                'is_admin' => false,
                'email_verified_at' => now(),
            ]
        );

        $moderator->assignRole('moderator');

        $this->command->info('Moderator user created: moderator@poskopajak.id / password');
    }
}
