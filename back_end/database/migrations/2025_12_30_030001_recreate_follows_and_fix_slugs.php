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
        // 1. Recreate Follows Table correctly (without ID column which causes UUID pivot issues)
        Schema::dropIfExists('follows');
        
        Schema::create('follows', function (Blueprint $table) {
            $table->foreignUuid('follower_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('followed_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['follower_id', 'followed_id']);
        });

        // 2. Backfill Slugs for all users
        $users = User::all();
        foreach ($users as $user) {
            if (empty($user->slug)) {
                $user->slug = Str::slug($user->nom, '_');
                $user->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
