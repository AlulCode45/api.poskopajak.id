<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call(RolePermissionSeeder::class);

        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);
        $admin->assignRole('admin');

        // Create moderator user
        $moderator = User::factory()->create([
            'name' => 'Moderator',
            'email' => 'moderator@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);
        $moderator->assignRole('moderator');

        // Create regular user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);
        $user->assignRole('user');

        // Create additional test users
        $additionalUsers = User::factory(5)->create();
        foreach ($additionalUsers as $additionalUser) {
            $additionalUser->assignRole('user');
        }
    }
}
