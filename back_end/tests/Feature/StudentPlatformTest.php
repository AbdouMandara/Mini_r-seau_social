<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentPlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_student_fields()
    {
        Storage::fake('public');

        $response = $this->postJson('/api/register', [
            'nom' => 'Student User',
            'password' => 'password123',
            'etablissement' => 'UY1',
            'filiere' => 'GL',
            'niveau' => '2',
            'photo_profil' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'nom' => 'Student User',
            'etablissement' => 'UY1',
            'filiere' => 'GL',
            'niveau' => '2',
        ]);
        $this->assertDatabaseMissing('users', ['region' => 'Adamaoua']);
    }

    public function test_user_can_update_profile_with_bio()
    {
        $user = User::factory()->create([
            'etablissement' => 'UY2',
            'filiere' => 'GLT',
            'niveau' => '1',
        ]);

        $response = $this->actingAs($user)->postJson('/api/user/update', [
            'nom' => 'Updated Name',
            'etablissement' => 'New Univ',
            'filiere' => 'SWE',
            'niveau' => '2',
            'bio' => 'My student bio',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'nom' => 'Updated Name',
            'bio' => 'My student bio',
            'filiere' => 'SWE',
        ]);
    }

    public function test_user_can_create_post_with_academic_tags()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/posts', [
            'description' => 'Calcul diff',
            'tag' => 'maths',
            'filiere' => 'GL',
            'niveau' => '1',
            'matiere' => 'Analyse I',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'description' => 'Calcul diff',
            'tag' => 'maths',
            'matiere' => 'Analyse I',
            'filiere' => 'GL',
        ]);
    }

    public function test_posts_can_be_filtered_by_tag_and_filiere()
    {
        $user = User::factory()->create();
        
        // Post 1: Study GL
        Post::factory()->create([
            'id_user' => $user->id,
            'tag' => 'etude',
            'filiere' => 'GL',
            'description' => 'Post GL Etude'
        ]);

        // Post 2: Fun SWE
        Post::factory()->create([
            'id_user' => $user->id,
            'tag' => 'divertissement',
            'filiere' => 'SWE',
            'description' => 'Post SWE Fun'
        ]);

        // Filter by Tag
        $response = $this->getJson('/api/posts?tag=etude');
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['description' => 'Post GL Etude']);

        // Filter by Filiere
        $response = $this->getJson('/api/posts?filiere=SWE');
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['description' => 'Post SWE Fun']);

        // Filter by both
        $response = $this->getJson('/api/posts?tag=etude&filiere=SWE');
        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }
}
