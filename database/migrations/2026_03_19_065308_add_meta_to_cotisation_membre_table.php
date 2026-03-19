<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cotisation_membre', function (Blueprint $table) {
            $table->json('meta')->nullable()->after('montant_restant');
        });
    }

    public function down(): void
    {
        Schema::table('cotisation_membre', function (Blueprint $table) {
            $table->dropColumn('meta');
        });
    }
};
