<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotisation_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotisation_id')->constrained('cotisations')->onDelete('cascade');
            $table->string('meta_key');
            $table->text('meta_value')->nullable();
            $table->timestamps();

            $table->unique(['cotisation_id', 'meta_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotisation_meta');
    }
};