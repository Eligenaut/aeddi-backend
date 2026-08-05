<?php

namespace Database\Seeders;

use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $promotions = [
            ['nom' => 'THE BOSS',   'annee' => 2019],
            ['nom' => 'LEADER',     'annee' => 2020],
            ['nom' => 'SOLIDAIRE',  'annee' => 2021],
            ['nom' => 'ELITE',      'annee' => 2022],
            ['nom' => 'LOYAL',      'annee' => 2023],
            ['nom' => 'THE BEST',   'annee' => 2024],
        ];

        foreach ($promotions as $p) {
            Promotion::firstOrCreate(
                ['annee' => $p['annee']],
                ['nom' => $p['nom']]
            );
        }
    }
}
