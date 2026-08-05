<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;


class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            PromotionSeeder::class,
            VilleQuartierSeeder::class,
            EtablissementParcoursNiveauSeeder::class,
            LogementSeeder::class,
            RolePermissionSeeder::class,
        ]);
    }
}
