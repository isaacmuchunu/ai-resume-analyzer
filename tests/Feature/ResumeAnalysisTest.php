<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Resume;
use App\Services\AnthropicService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResumeAnalysisTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup test tenant
        $this->tenant = Tenant::create([
            'id' => 'test-tenant',
            'name' => 'Test Corporation',
            'subdomain' => 'test',
            'plan' => 'professional',
            'is_active' => true,
        ]);

        $this->tenant->makeCurrent();
        
        // Create test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);
    }

    public function test_user_can_upload_resume(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('resume.pdf', 1000, 'application/pdf');

        $response = $this->actingAs($this->user)
            ->post('/resumes/upload', [
                'file' => $file,
                'target_role' => 'Software Engineer',
                'target_industry' => 'Technology',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'resume' => [
                'id',
                'original_filename',
                'parsing_status',
                'analysis_status',
            ],
        ]);

        $this->assertDatabaseHas('resumes', [
            'user_id' => $this->user->id,
            'original_filename' => 'resume.pdf',
            'parsing_status' => 'pending',
            'analysis_status' => 'pending',
        ]);
    }

    public function test_resume_upload_validates_file_type(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('resume.txt', 1000, 'text/plain');

        $response = $this->actingAs($this->user)
            ->post('/resumes/upload', [
                'file' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_user_can_view_resume_list(): void
    {
        $resume = Resume::factory()->create([
            'user_id' => $this->user->id,
            'original_filename' => 'test-resume.pdf',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/resumes');

        $response->assertStatus(200);
        $response->assertSee('test-resume.pdf');
    }

    public function test_user_can_view_resume_details(): void
    {
        $resume = Resume::factory()->create([
            'user_id' => $this->user->id,
            'original_filename' => 'test-resume.pdf',
            'parsing_status' => 'completed',
            'analysis_status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->get("/resumes/{$resume->id}");

        $response->assertStatus(200);
    }

    public function test_user_cannot_view_other_users_resumes(): void
    {
        $otherUser = User::factory()->create();
        $resume = Resume::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get("/resumes/{$resume->id}");

        $response->assertStatus(403);
    }

    public function test_resume_analysis_creates_results(): void
    {
        $resume = Resume::factory()->create([
            'user_id' => $this->user->id,
            'parsing_status' => 'completed',
            'metadata' => [
                'parsed_data' => [
                    'raw_text' => 'John Doe\nSoftware Engineer\nExperience with PHP, Laravel, JavaScript',
                ],
            ],
        ]);

        // Mock the Anthropic service
        $this->mock(AnthropicService::class, function ($mock) {
            $mock->shouldReceive('analyzeResume')
                ->once()
                ->andReturn([
                    'overall_score' => 85,
                    'ats_score' => 80,
                    'content_score' => 90,
                    'format_score' => 85,
                    'keyword_score' => 75,
                    'recommendations' => ['Add more quantified achievements'],
                    'analysis_text' => 'Test analysis',
                ]);

            $mock->shouldReceive('extractSkills')
                ->once()
                ->andReturn([
                    'technical_skills' => ['PHP', 'Laravel', 'JavaScript'],
                    'soft_skills' => ['Communication'],
                ]);
        });

        $response = $this->actingAs($this->user)
            ->post("/resumes/{$resume->id}/reanalyze");

        $response->assertStatus(200);

        $this->assertDatabaseHas('analysis_results', [
            'resume_id' => $resume->id,
            'overall_score' => 85,
        ]);
    }

    public function test_api_authentication_with_api_key(): void
    {
        $user = User::factory()->create();
        $apiKey = $user->generateApiKey();

        $response = $this->withHeaders([
            'X-API-Key' => $apiKey,
            'Accept' => 'application/json',
        ])->get('/api/user');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'email',
            'first_name',
            'last_name',
        ]);
    }

    public function test_api_requires_authentication(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Authentication required',
        ]);
    }

    public function test_rate_limiting_works(): void
    {
        // Make requests to exceed the rate limit
        for ($i = 0; $i < 61; $i++) {
            $response = $this->get('/');
        }

        $response->assertStatus(429);
    }

    public function test_tenant_isolation(): void
    {
        // Create another tenant
        $otherTenant = Tenant::create([
            'id' => 'other-tenant',
            'name' => 'Other Corporation',
            'subdomain' => 'other',
            'plan' => 'starter',
            'is_active' => true,
        ]);

        $otherTenant->makeCurrent();

        $otherUser = User::factory()->create([
            'email' => 'other@example.com',
        ]);

        $otherResume = Resume::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        // Switch back to original tenant
        $this->tenant->makeCurrent();

        // User should not be able to see the other tenant's resume
        $response = $this->actingAs($this->user)
            ->get("/resumes/{$otherResume->id}");

        $response->assertStatus(404);
    }
}