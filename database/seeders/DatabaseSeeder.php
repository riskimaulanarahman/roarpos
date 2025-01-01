<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Category;
use App\Models\Discount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        \App\Models\User::factory()->create([
            'name' => 'Cashier POS',
            'email' => 'cashierpos@gmail.com',
            'password' => Hash::make('12345678'),
        ]);
        \App\Models\User::factory()->create([
            'name' => 'Admin POS',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('12345678'),
        ]);

        Discount::insert([
            [
                'name' => 'DISC10',
                'description' => '10% discount for all products',
                'type' => 'percentage',
                'value' => 10,
                'status' => 'active',
                'expired_date' => now()->addMonth(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'DISC15',
                'description' => '15.000 discount for all products',
                'type' => 'fixed',
                'value' => 15000,
                'status' => 'active',
                'expired_date' => now()->addMonth(),
                'created_at' => now(),
                'updated_at' => now(),
            ],


        ]);

        Category::insert([
            [
                'name' => 'Food',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Drink',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Snack',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        $this->call([
            ProductSeeder::class,
            AdditionalChargesSeeder::class
        ]);

    }
}
