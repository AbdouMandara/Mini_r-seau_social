<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $nom = env('ADMIN_NOM');
        $password = env('ADMIN_PASSWORD');

        User::create([
            'nom' => $nom,
            'password' => $password,
            'is_admin' => true,
            'photo_profil' => 'https://ui-avatars.com/api/?name=' . urlencode($nom),
        ]);
    }
}
