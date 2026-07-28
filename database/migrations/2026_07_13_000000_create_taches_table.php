<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taches', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->constrained('users')->onDelete('cascade');
            $table->enum('statut', ['en_attente', 'en_cours', 'terminee', 'annulee'])->default('en_attente');
            $table->enum('priorite', ['basse', 'moyenne', 'haute', 'urgente'])->default('moyenne');
            $table->date('date_echeance')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['assigned_to', 'statut']);
            $table->index(['assigned_by', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taches');
    }
};
