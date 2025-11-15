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
        Schema::create('cotisation_membre', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('cotisation_id')->constrained()->onDelete('cascade');
            $table->enum('statut', ['non_paye', 'paye', 'reste'])->default('non_paye');
            $table->decimal('montant_restant', 10, 2)->nullable();
            $table->timestamps();
            
            // Index unique pour éviter les doublons
            $table->unique(['user_id', 'cotisation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotisation_membre');
    }
};
