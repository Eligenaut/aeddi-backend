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
        Schema::dropIfExists('pending_registrations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('pending_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('prenom');
            $table->string('nom');
            $table->string('email')->unique();
            $table->string('etablissement');
            $table->string('parcours');
            $table->string('niveau');
            $table->string('promotion');
            $table->string('logement');
            $table->string('bloc_campus')->nullable();
            $table->string('quartier')->nullable();
            $table->string('telephone');
            $table->text('image')->nullable();
            $table->string('image_name')->nullable();
            $table->string('image_type')->nullable();
            $table->string('verification_code', 6);
            $table->timestamp('verification_code_expires_at');
            $table->timestamps();
        });
    }
};
