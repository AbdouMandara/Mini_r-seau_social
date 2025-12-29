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
        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id_post');
            $table->string('img_post')->nullable();
            $table->string('description', 100);
            $table->boolean('is_delete')->default(false);
            $table->foreignId('id_user')->constrained('users')->onDelete('cascade');
            $table->boolean('allow_comments')->default(true); // Added this as it's in the description.md (Section 5)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
