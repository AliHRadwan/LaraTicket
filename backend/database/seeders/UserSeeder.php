<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->count(10)->create();
        User::factory()->unverified()->count(3)->create();

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@laraticket.duckdns.org',
            'password' => Hash::make('admin123456'),
            'is_admin' => true,
        ]);

        User::factory()->create([
            'name' => 'User',
            'email' => 'user@laraticket.duckdns.org',
            'password' => Hash::make('user123456'),
            'is_admin' => false,
        ]);
    }
}
