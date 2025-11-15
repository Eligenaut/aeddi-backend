<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class SubRoleTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer quelques utilisateurs de test avec des sous-rôles
        $users = [
            [
                'name' => 'Jean Dupont',
                'nom' => 'Dupont',
                'prenom' => 'Jean',
                'email' => 'president@aeddi.com',
                'password' => bcrypt('password'),
                'role' => 'bureau',
                'sub_role' => 'president',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Marie Martin',
                'nom' => 'Martin',
                'prenom' => 'Marie',
                'email' => 'tresorier@aeddi.com',
                'password' => bcrypt('password'),
                'role' => 'bureau',
                'sub_role' => 'tresorier',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Pierre Durand',
                'nom' => 'Durand',
                'prenom' => 'Pierre',
                'email' => 'commission.sport@aeddi.com',
                'password' => bcrypt('password'),
                'role' => 'bureau',
                'sub_role' => 'commission_sport',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Sophie Bernard',
                'nom' => 'Bernard',
                'prenom' => 'Sophie',
                'email' => 'commission.communication@aeddi.com',
                'password' => bcrypt('password'),
                'role' => 'bureau',
                'sub_role' => 'commission_communication',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}