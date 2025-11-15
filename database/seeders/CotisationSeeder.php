<?php

namespace Database\Seeders;

use App\Models\Cotisation;
use App\Models\CotisationMembre;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CotisationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pas de cotisations de test - seulement celles créées via l'interface admin
        // La base de données commencera vide, les cotisations seront ajoutées via le dashboard
    }
}
