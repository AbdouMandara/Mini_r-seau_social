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
            $table->id('id_notif');
            $table->foreignId('id_user_target')->constrained('users', 'id')->onDelete('cascade');
            $table->foreignId('id_user_author')->constrained('users', 'id')->onDelete('cascade');
            $table->foreignId('id_post')->constrained('posts', 'id_post')->onDelete('cascade');
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
