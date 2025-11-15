<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RecoveryCode;
use App\Models\User;

class RecoveryCodeTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer l'utilisateur admin (trésorier)
        $admin = User::where('role', 'admin')->first();
        
        if (!$admin) {
            $this->command->error('Aucun admin trouvé. Créez d\'abord un utilisateur avec le rôle "admin".');
            return;
        }

        // Emails de test autorisés
        $testEmails = [
            'test1@example.com',
            'test2@example.com',
            'etudiant@example.com',
            'demo@aeddi.com'
        ];

        foreach ($testEmails as $email) {
            RecoveryCode::createCodeForEmail($email, $admin->id);
            $this->command->info("Code créé pour l'email: {$email}");
        }

        $this->command->info('Codes de récupération de test créés avec succès !');
    }
}
