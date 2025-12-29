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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id_notif')->primary();
            $table->foreignUuid('id_user_target')->constrained('users', 'id')->onDelete('cascade');
            $table->foreignUuid('id_user_author')->constrained('users', 'id')->onDelete('cascade');
            $table->foreignUuid('id_post')->constrained('posts', 'id_post')->onDelete('cascade');
            $table->string('type'); // 'like' or 'comment'
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
