<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\ContactType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $roles = ['Admin', 'User'];

        foreach ($roles as $role) {
            Role::create([
                'name' => $role
            ]);
        }

        User::factory()->create([
            'email' => 'admin@mailinator.com',
            'role_id' => Role::ADMIN
        ]);

        $this->call([
            PurchaseCategorySeeder::class,
            PurchaseStatusSeeder::class,
            ContactTypeSeeder::class
        ]);
    }
}
