<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'description' => fake()->text(100),
            'tag' => 'etude',
            'filiere' => 'GL',
            'niveau' => '1',
            'id_user' => User::factory(),
            'allow_comments' => true,
        ];
    }
}
