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
        Schema::table('badges', function (Blueprint $table) {
            $table->string('criteria_type')->nullable()->after('color'); // 'posts_count', 'likes_received'
            $table->integer('criteria_value')->default(0)->after('criteria_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('badges', function (Blueprint $table) {
            $table->dropColumn(['criteria_type', 'criteria_value']);
        });
    }
};
