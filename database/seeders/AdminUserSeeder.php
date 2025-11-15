<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un utilisateur admin s'il n'existe pas
        User::firstOrCreate(
            ['email' => 'admin@aeddi.com'],
            [
                'name' => 'Administrateur AEDDI',
                'email' => 'admin@aeddi.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin'
            ]
        );

        // Mettre à jour le rôle de l'utilisateur admin existant
        User::where('email', 'admin@aeddi.com')->update(['role' => 'admin']);
    }
}
