<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Badge;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Badge "Validateur" : 10 posts
        Badge::firstOrCreate(
            ['name' => 'Validateur'],
            [
                'description' => 'A posté 10 solutions ou ressources.',
                'icon' => 'check-circle', // Heroicons name or emoji
                'color' => '#10B981', // Emerald 500
                'criteria_type' => 'posts_count',
                'criteria_value' => 10
            ]
        );

        // Badge "Populaire" : 100 likes reçus
        Badge::firstOrCreate(
            ['name' => 'Populaire'],
            [
                'description' => 'A reçu 100 likes sur ses publications.',
                'icon' => 'fire',
                'color' => '#F59E0B', // Amber 500
                'criteria_type' => 'likes_received_count', // Updated key to be explicit
                'criteria_value' => 100
            ]
        );

        // Badge "Expert" : 50 posts
        Badge::firstOrCreate(
            ['name' => 'Expert'],
            [
                'description' => 'A posté 50 solutions ou ressources.',
                'icon' => 'academic-cap',
                'color' => '#8B5CF6', // Violet 500
                'criteria_type' => 'posts_count',
                'criteria_value' => 50
            ]
        );
    }
}
