<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activites', function (Blueprint $table) {
            $table->string('lieu')->nullable()->after('statut');
            $table->string('categorie')->default('Autre')->after('lieu');
            $table->string('image')->nullable()->after('categorie');
        });
    }

    public function down(): void
    {
        Schema::table('activites', function (Blueprint $table) {
            $table->dropColumn(['lieu', 'categorie', 'image']);
        });
    }
};