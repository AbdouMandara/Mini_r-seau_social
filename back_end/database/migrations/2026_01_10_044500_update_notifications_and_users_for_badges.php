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
        Schema::table('notifications', function (Blueprint $table) {
            $table->uuid('id_user_author')->nullable()->change();
            $table->uuid('id_badge')->nullable()->after('id_post');
            $table->foreign('id_badge')->references('id_badge')->on('badges')->onDelete('cascade');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('current_title')->nullable()->after('bio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['id_badge']);
            $table->dropColumn('id_badge');
            $table->uuid('id_user_author')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('current_title');
        });
    }
};
