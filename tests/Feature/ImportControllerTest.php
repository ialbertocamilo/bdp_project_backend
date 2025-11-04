<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportControllerTest extends RefreshDatabase
{
    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->assignRole('Gestor');
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    public function test_get_template()
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}"
        ])->get('/api/import/template');

        $response->assertStatus(200);
        $this->assertTrue($response->headers->has('content-disposition'));
    }

    public function test_import_csv_projects()
    {
        Storage::fake('local');

        $csv = "name,description\nTest Project 1,Description 1\nTest Project 2,Description 2";
        $file = UploadedFile::fromString($csv, 'projects.csv', 'text/csv');

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}"
        ])->post('/api/import/projects', [
            'file' => $file
        ]);

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('imported'));
        $this->assertEquals(0, $response->json('failed'));

        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project 1',
            'user_id' => $this->user->id
        ]);
    }

    public function test_import_missing_required_field()
    {
        Storage::fake('local');

        $csv = "description\nJust a description";
        $file = UploadedFile::fromString($csv, 'projects.csv', 'text/csv');

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}"
        ])->post('/api/import/projects', [
            'file' => $file
        ]);

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('imported'));
        $this->assertGreaterThan(0, count($response->json('errors')));
    }

    public function test_validate_file()
    {
        Storage::fake('local');

        $csv = "name,description\nTest Project,Test";
        $file = UploadedFile::fromString($csv, 'projects.csv', 'text/csv');

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}"
        ])->post('/api/import/validate', [
            'file' => $file
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('valid'));
    }

    public function test_import_history()
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}"
        ])->get('/api/import/history');

        $response->assertStatus(200);
        $this->assertIsArray($response->json());
    }

    public function test_import_requires_authentication()
    {
        $response = $this->post('/api/import/projects', [
            'file' => UploadedFile::fromString('test', 'test.csv')
        ]);

        $response->assertStatus(401);
    }
}
