<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate existing data from taches.assigned_to to tache_user
        $taches = DB::table('taches')->whereNotNull('assigned_to')->get();
        foreach ($taches as $tache) {
            DB::table('tache_user')->insert([
                'tache_id'    => $tache->id,
                'user_id'     => $tache->assigned_to,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // Drop foreign key and column
        Schema::table('taches', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('cascade');
        });

        // Restore data from pivot (only first assignee)
        $pivots = DB::table('tache_user')
            ->select('tache_id', 'user_id')
            ->orderBy('tache_id')
            ->orderBy('id')
            ->get()
            ->groupBy('tache_id');

        foreach ($pivots as $tacheId => $items) {
            DB::table('taches')->where('id', $tacheId)->update(['assigned_to' => $items->first()->user_id]);
        }
    }
};
