<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Fix Follows Table: Not needed here because 030001 recreates it.
        // Dropping 'id' in SQLite causes errors if it's a primary key.
        /*
        if (Schema::hasColumn('follows', 'id')) {
            Schema::table('follows', function (Blueprint $table) {
                $table->dropColumn('id');
            });
        }
        */

        // 2. Backfill Slugs
        $users = User::whereNull('slug')->get();
        foreach ($users as $user) {
            $user->slug = Str::slug($user->nom, '_');
            $user->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
