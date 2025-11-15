<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RecoveryCode;

class RecoveryCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer 10 codes de récupération pour les tests
        for ($i = 0; $i < 10; $i++) {
            RecoveryCode::createCode();
        }
        
        // Créer un code spécifique pour les tests
        RecoveryCode::create([
            'code' => 'TEST1234',
            'used' => false,
            'created_by' => null
        ]);
        
        $this->command->info('Codes de récupération créés avec succès !');
        $this->command->info('Code de test: TEST1234');
    }
}
