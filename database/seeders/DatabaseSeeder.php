<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Use the comprehensive demo seeder
        $this->call([
            UpdatedDemoSeeder::class,
            FinanceCategorySeeder::class,
            RawMaterialSeeder::class,
            ProductRecipeSeeder::class,
            EmployeeSeeder::class,
        ]);
    }
}
