<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etablissements', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->timestamps();
        });

        Schema::create('parcours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->onDelete('cascade');
            $table->string('nom');
            $table->timestamps();
        });

        Schema::create('niveaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcours_id')->constrained('parcours')->onDelete('cascade');
            $table->string('nom');
            $table->timestamps();
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->integer('annee')->unique();
            $table->timestamps();
        });

        Schema::create('types_logement', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->timestamps();
        });

        Schema::create('options_campus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_logement_id')->nullable()->constrained('types_logement')->onDelete('cascade');
            $table->string('nom');
            $table->timestamps();
        });

        Schema::create('sections_campus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_campus_id')->constrained('options_campus')->onDelete('cascade');
            $table->string('nom');
            $table->timestamps();
        });

        Schema::create('blocs_campus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_campus_id')->constrained('sections_campus')->onDelete('cascade');
            $table->string('nom');
            $table->timestamps();
        });

        Schema::create('quartiers', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocs_campus');
        Schema::dropIfExists('sections_campus');
        Schema::dropIfExists('options_campus');
        Schema::dropIfExists('quartiers');
        Schema::dropIfExists('types_logement');
        Schema::dropIfExists('niveaux');
        Schema::dropIfExists('parcours');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('etablissements');
    }
};
