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
        Schema::table('users', function (Blueprint $table) {
            $table->string('nom')->nullable();
            $table->string('prenom')->nullable();
            $table->string('etablissement')->nullable();
            $table->string('parcours')->nullable();
            $table->string('niveau')->nullable();
            $table->string('promotion')->nullable();
            $table->string('logement')->nullable();
            $table->string('bloc_campus')->nullable();
            $table->string('quartier')->nullable();
            $table->string('telephone')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('verification_code', 6)->nullable();
            $table->timestamp('verification_code_expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nom',
                'prenom',
                'etablissement',
                'parcours',
                'niveau',
                'promotion',
                'logement',
                'bloc_campus',
                'quartier',
                'telephone',
                'profile_image',
                'verification_code',
                'verification_code_expires_at'
            ]);
        });
    }
};
