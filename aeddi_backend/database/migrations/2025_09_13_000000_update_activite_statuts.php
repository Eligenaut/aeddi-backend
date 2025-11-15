<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Table activites : planifiee -> en_attente
        DB::table('activites')->where('statut', 'planifiee')->update(['statut' => 'en_attente']);

        // Table activite_membre :
        // inscrit -> en_attente
        DB::table('activite_membre')->where('statut_participation', 'inscrit')->update(['statut_participation' => 'en_attente']);
        // present -> en_cours
        DB::table('activite_membre')->where('statut_participation', 'present')->update(['statut_participation' => 'en_cours']);
        // absent, excuse -> terminee
        DB::table('activite_membre')->whereIn('statut_participation', ['absent', 'excuse'])->update(['statut_participation' => 'terminee']);
    }

    public function down(): void
    {
        // Optionnel : remettre les anciens statuts si besoin
        // DB::table('activites')->where('statut', 'en_attente')->update(['statut' => 'planifiee']);
        // DB::table('activite_membre')->where('statut_participation', 'en_attente')->update(['statut_participation' => 'inscrit']);
        // DB::table('activite_membre')->where('statut_participation', 'en_cours')->update(['statut_participation' => 'present']);
        // DB::table('activite_membre')->where('statut_participation', 'terminee')->update(['statut_participation' => 'absent']);
    }
};
