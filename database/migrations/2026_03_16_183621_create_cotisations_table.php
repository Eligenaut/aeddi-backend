<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotisations', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description');
            
            $table->decimal('montant_ancien', 10, 2)->default(0);
            $table->decimal('montant_novice', 10, 2)->default(0);
            
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('lieu')->nullable();
            $table->string('image_lieu')->nullable();
            $table->string('statut')->default('en_cours');
            $table->string('image')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotisations');
    }
};