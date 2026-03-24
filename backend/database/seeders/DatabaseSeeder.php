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
        if (app()->environment('local')) {
            $this->call([
                UserSeeder::class,
                EventSeeder::class,
                OrderSeeder::class,
                PaymentSeeder::class,
            ]);
        } else {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@laraticket.duckdns.org',
                'password' => Hash::make('admin123456'),
                'is_admin' => true,
            ]);
        }
    }
}
