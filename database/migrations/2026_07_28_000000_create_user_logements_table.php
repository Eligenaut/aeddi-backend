<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_logements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('type_logement_id')->nullable()->constrained('types_logement')->nullOnDelete();
            $table->foreignId('option_campus_id')->nullable()->constrained('options_campus')->nullOnDelete();
            $table->foreignId('section_campus_id')->nullable()->constrained('sections_campus')->nullOnDelete();
            $table->foreignId('bloc_campus_id')->nullable()->constrained('blocs_campus')->nullOnDelete();
            $table->foreignId('quartier_id')->nullable()->constrained('quartiers')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'type_logement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_logements');
    }
};
