<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $create) {
            $create->uuid('id_activity')->primary();
            $create->uuid('id_user');
            $create->string('action'); // e.g., 'post', 'like', 'comment', 'follow'
            $create->text('details')->nullable(); // e.g., 'A liké le post #123'
            $create->timestamps();

            $create->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
