<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResumeUploadRequest;
use App\Jobs\ProcessResumeJob;
use App\Models\Resume;
use App\Services\FileProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ResumeController extends Controller
{
    public function __construct(
        private FileProcessingService $fileProcessingService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $tenant = app('currentTenant');

        $resumes = $user->resumes()
            ->with(['analysisResults' => function ($query) {
                $query->latest();
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Resumes/Index', [
            'tenant' => [
                'name' => $tenant->name,
                'branding' => $tenant->getBrandingData(),
            ],
            'resumes' => $resumes->through(function ($resume) {
                return [
                    'id' => $resume->id,
                    'original_filename' => $resume->original_filename,
                    'file_size' => $resume->file_size,
                    'file_type' => $resume->file_type,
                    'parsing_status' => $resume->parsing_status,
                    'analysis_status' => $resume->analysis_status,
                    'created_at' => $resume->created_at,
                    'latest_analysis' => $resume->latestAnalysis() ? [
                        'id' => $resume->latestAnalysis()->id,
                        'overall_score' => $resume->latestAnalysis()->overall_score,
                        'ats_score' => $resume->latestAnalysis()->ats_score,
                        'content_score' => $resume->latestAnalysis()->content_score,
                        'format_score' => $resume->latestAnalysis()->format_score,
                        'keyword_score' => $resume->latestAnalysis()->keyword_score,
                        'created_at' => $resume->latestAnalysis()->created_at,
                    ] : null,
                ];
            }),
        ]);
    }

    public function show(Request $request, Resume $resume)
    {
        $this->authorize('view', $resume);

        $tenant = app('currentTenant');

        $resume->load(['analysisResults' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        return Inertia::render('Resumes/Show', [
            'tenant' => [
                'name' => $tenant->name,
                'branding' => $tenant->getBrandingData(),
            ],
            'resume' => [
                'id' => $resume->id,
                'original_filename' => $resume->original_filename,
                'file_size' => $resume->file_size,
                'file_type' => $resume->file_type,
                'parsing_status' => $resume->parsing_status,
                'analysis_status' => $resume->analysis_status,
                'metadata' => $resume->metadata,
                'created_at' => $resume->created_at,
                'updated_at' => $resume->updated_at,
            ],
            'analysis_results' => $resume->analysisResults->map(function ($analysis) {
                return [
                    'id' => $analysis->id,
                    'analysis_type' => $analysis->analysis_type,
                    'overall_score' => $analysis->overall_score,
                    'ats_score' => $analysis->ats_score,
                    'content_score' => $analysis->content_score,
                    'format_score' => $analysis->format_score,
                    'keyword_score' => $analysis->keyword_score,
                    'detailed_scores' => $analysis->detailed_scores,
                    'recommendations' => $analysis->recommendations,
                    'extracted_skills' => $analysis->extracted_skills,
                    'missing_skills' => $analysis->missing_skills,
                    'keywords' => $analysis->keywords,
                    'sections_analysis' => $analysis->sections_analysis,
                    'ai_insights' => $analysis->ai_insights,
                    'created_at' => $analysis->created_at,
                ];
            }),
        ]);
    }

    public function upload(ResumeUploadRequest $request): JsonResponse
    {
        $user = $request->user();
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : $this->getDefaultTenant($request);
        $file = $request->file('file');

        try {
            // Generate unique filename with tenant prefix
            $filename = sprintf(
                '%s_%s_%s.%s',
                $tenant->id,
                $user->id,
                uniqid(),
                $file->getClientOriginalExtension()
            );

            // Store file in tenant-specific directory
            $path = $file->storeAs(
                "tenants/{$tenant->id}/resumes",
                $filename,
                'local'
            );

            // Create resume record
            $resume = $user->resumes()->create([
                'filename' => $filename,
                'original_filename' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_type' => $file->getMimeType(),
                'storage_path' => $path,
                'parsing_status' => 'pending',
                'analysis_status' => 'pending',
                'metadata' => [
                    'target_role' => $request->input('target_role'),
                    'target_industry' => $request->input('target_industry'),
                    'uploaded_at' => now()->toISOString(),
                ],
            ]);

            // Dispatch job to process resume
            ProcessResumeJob::dispatch($resume);

            return response()->json([
                'success' => true,
                'message' => 'Resume uploaded successfully! Processing will begin shortly.',
                'resume' => [
                    'id' => $resume->id,
                    'original_filename' => $resume->original_filename,
                    'parsing_status' => $resume->parsing_status,
                    'analysis_status' => $resume->analysis_status,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload resume. Please try again.',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function download(Request $request, Resume $resume)
    {
        $this->authorize('view', $resume);

        if (!Storage::exists($resume->storage_path)) {
            abort(404, 'File not found');
        }

        return Storage::download(
            $resume->storage_path,
            $resume->original_filename
        );
    }

    public function delete(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('delete', $resume);

        try {
            // Delete file from storage
            if (Storage::exists($resume->storage_path)) {
                Storage::delete($resume->storage_path);
            }

            // Delete resume and related analysis results
            $resume->delete();

            return response()->json([
                'success' => true,
                'message' => 'Resume deleted successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete resume. Please try again.',
            ], 500);
        }
    }

    public function reanalyze(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        if ($resume->parsing_status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Resume must be parsed before analysis.',
            ], 400);
        }

        try {
            // Update analysis status
            $resume->update(['analysis_status' => 'pending']);

            // Dispatch analysis job
            ProcessResumeJob::dispatch($resume, true); // true = analysis only

            return response()->json([
                'success' => true,
                'message' => 'Resume analysis has been queued.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to queue analysis. Please try again.',
            ], 500);
        }
    }
}