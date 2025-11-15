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
        Schema::create('activite_membre', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activite_id')->constrained('activites')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('statut_participation', ['en_cours', 'terminee', 'en_attente', 'annulee'])->default('en_cours');
            $table->text('commentaire')->nullable();
            $table->timestamps();
            
            // Index unique pour éviter les doublons
            $table->unique(['activite_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activite_membre');
    }
};
