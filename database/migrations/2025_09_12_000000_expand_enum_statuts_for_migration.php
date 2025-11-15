<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        // Elargir l'ENUM de activites.statut
        Schema::table('activites', function (Blueprint $table) {
            $table->enum('statut', [
                'en_cours', 'terminee', 'en_attente', 'annulee',
                'planifiee'
            ])->default('en_cours')->change();
        });

        // Elargir l'ENUM de activite_membre.statut_participation
        Schema::table('activite_membre', function (Blueprint $table) {
            $table->enum('statut_participation', [
                'en_cours', 'terminee', 'en_attente', 'annulee',
                'inscrit', 'present', 'absent', 'excuse'
            ])->default('en_cours')->change();
        });
    }

    public function down(): void
    {
        // Ne rien faire (on restreindra après migration des données)
    }
};
