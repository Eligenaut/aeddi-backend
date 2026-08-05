<?php

namespace Database\Seeders;

use App\Models\Ville;
use App\Models\Quartier;
use Illuminate\Database\Seeder;

class VilleQuartierSeeder extends Seeder
{
    public function run(): void
    {
        $ville = Ville::create(['nom' => 'Diego Suarez (Antsiranana)']);

        $quartiers = [
            'Ambalakazaha', 'Ambalavola', 'Ambohimitsinjo', 'Anamakia',
            'Avenir', 'Bazar kely', 'Cap-Diego', 'Cité ouvrière',
            'Grand Pavois', 'Lazaret Nord', 'Lazaret Sud', 'Mahatsara',
            'Mangarivotra', 'Manongalaza', 'Morafeno', 'Place Kabary',
            'Scama', 'Soafeno', 'Tanambao III', 'Tanambao IV',
            'Tanambao Nord', 'Tanambao Sud', 'Tanambao tsena',
            'Tanambao V', 'Tsaramandroso',
        ];

        foreach ($quartiers as $nom) {
            Quartier::create([
                'nom' => $nom,
                'ville_id' => $ville->id,
            ]);
        }
    }
}
