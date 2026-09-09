<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            DepartemenSeeder::class,
            JabatanSeeder::class,
            ShiftSeeder::class,
            JenisCutiSeeder::class,
            AlasanCutiSeeder::class,
            KaryawanSeeder::class,
        ]);
    }
}
