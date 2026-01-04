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
        Schema::table('users', function (Blueprint $table) {
            // Remove region
            $table->dropColumn('region');
            
            // Add new student fields
            $table->string('etablissement')->nullable()->after('photo_profil');
            $table->enum('filiere', ['GL', 'GLT', 'SWE', 'MVC', 'LTM'])->nullable()->after('etablissement');
            $table->enum('niveau', ['1', '2'])->nullable()->after('filiere');
            
            // Rename description to bio (description was added in a separate migration)
            if (Schema::hasColumn('users', 'description')) {
                $table->renameColumn('description', 'bio');
            }
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->enum('tag', ['etude', 'divertissement', 'info', 'programmation', 'maths', 'devoir'])->nullable()->after('description');
            $table->enum('filiere', ['GL', 'GLT', 'SWE', 'MVC', 'LTM'])->nullable()->after('tag');
            $table->enum('niveau', ['1', '2'])->nullable()->after('filiere');
            $table->string('matiere')->nullable()->after('niveau');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('region')->nullable()->after('photo_profil');
            $table->dropColumn(['etablissement', 'filiere', 'niveau']);
            if (Schema::hasColumn('users', 'bio')) {
                $table->renameColumn('bio', 'description');
            }
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['tag', 'filiere', 'niveau', 'matiere']);
        });
    }
};
