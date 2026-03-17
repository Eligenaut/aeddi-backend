<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@aeddi.com'],
            [
                'name'                         => 'Administrateur AEDDI',
                'email'                        => 'admin@aeddi.com',
                'password'                     => Hash::make('admin123'),
                'role'                         => 'admin',
                'sub_role'                     => null,
                'avatar'                       => null,
                'google_id'                    => null,
                'verification_code'            => null,
                'verification_code_expires_at' => null,
            ]
        );

        // Stocker les infos secondaires dans user_meta
        $metas = [
            'nom'           => 'AEDDI',
            'prenom'        => 'Administrateur',
            'etablissement' => null,
            'parcours'      => null,
            'niveau'        => null,
            'promotion'     => null,
            'logement'      => null,
            'bloc_campus'   => null,
            'quartier'      => null,
            'telephone'     => null,
            'profile_image' => null,
        ];

        foreach ($metas as $key => $value) {
            $admin->setMeta($key, $value ?? '');
        }

        // S'assurer que le rôle admin est bien défini
        $admin->update(['role' => 'admin']);
    }
}
