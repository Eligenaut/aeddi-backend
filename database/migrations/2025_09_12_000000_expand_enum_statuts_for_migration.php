<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // activites.statut
        Schema::table('activites', function (Blueprint $table) {
            $table->string('statut')->default('en_cours')->change();
        });

        // Ajouter la contrainte check PostgreSQL
        DB::statement("ALTER TABLE activites
            ADD CONSTRAINT check_statut CHECK (statut IN ('en_cours','terminee','en_attente','annulee','planifiee'))");

        // activite_membre.statut_participation
        Schema::table('activite_membre', function (Blueprint $table) {
            $table->string('statut_participation')->default('en_cours')->change();
        });

        DB::statement("ALTER TABLE activite_membre
            ADD CONSTRAINT check_statut_participation CHECK (statut_participation IN ('en_cours','terminee','en_attente','annulee','inscrit','present','absent','excuse'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE activites DROP CONSTRAINT IF EXISTS check_statut');
        DB::statement('ALTER TABLE activite_membre DROP CONSTRAINT IF EXISTS check_statut_participation');
    }
};
