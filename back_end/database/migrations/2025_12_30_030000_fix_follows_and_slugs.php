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
        // 1. Fix Follows Table: Drop 'id' column if it exists, as pivot tables don't need it and it causes UUID errors on attach()
        if (Schema::hasColumn('follows', 'id')) {
            Schema::table('follows', function (Blueprint $table) {
                // We cannot easily drop a primary key column if constraints exist, 
                // but commonly for UUID primary keys in simple migrations just dropping column might work 
                // or we need to drop PK first.
                // Simpler approach for dev: Recreate table without ID.
                $table->dropColumn('id');
            });
        }

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
