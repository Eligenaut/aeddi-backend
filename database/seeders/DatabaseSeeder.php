<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Créer l'utilisateur admin par défaut
        User::create([
            'name' => 'Admin',
            'email' => 'admin@aeddi.com',
            'password' => Hash::make('admin123'),
            'email_verified_at' => now(),
        ]);

        // Exécuter le seeder des cotisations
        $this->call(CotisationSeeder::class);
    }
}
