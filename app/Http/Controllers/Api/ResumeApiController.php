<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use App\Jobs\ProcessResumeJob;
use App\Services\FileProcessingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ResumeApiController extends Controller
{
    protected FileProcessingService $fileProcessingService;

    public function __construct(FileProcessingService $fileProcessingService)
    {
        $this->fileProcessingService = $fileProcessingService;
    }

    /**
     * Get all resumes for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $resumes = $user->resumes()
            ->with(['analysisResult', 'versions'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $resumes->items(),
            'pagination' => [
                'current_page' => $resumes->currentPage(),
                'last_page' => $resumes->lastPage(),
                'per_page' => $resumes->perPage(),
                'total' => $resumes->total(),
            ],
        ]);
    }

    /**
     * Upload and process a new resume
     */
    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,doc,docx|max:5120', // 5MB max
            'name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();
            $file = $request->file('file');
            
            // Generate unique filename
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            
            // Store file in tenant-specific directory
            $path = $file->storeAs('resumes', $filename, 'tenant');
            
            // Create resume record
            $resume = $user->resumes()->create([
                'name' => $request->name ?: $file->getClientOriginalName(),
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'status' => 'processing',
            ]);

            // Dispatch processing job
            ProcessResumeJob::dispatch($resume);

            return response()->json([
                'success' => true,
                'data' => $resume->load('analysisResult'),
                'message' => 'Resume uploaded successfully and is being processed.',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific resume
     */
    public function show(Request $request, Resume $resume): JsonResponse
    {
        // Ensure user can access this resume
        if ($resume->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Resume not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $resume->load([
                'analysisResult',
                'sections',
                'versions',
                'suggestions',
                'jobOptimizations'
            ]),
        ]);
    }

    /**
     * Delete a resume
     */
    public function delete(Request $request, Resume $resume): JsonResponse
    {
        // Ensure user can access this resume
        if ($resume->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Resume not found.',
            ], 404);
        }

        try {
            // Delete file from storage
            if ($resume->file_path && Storage::disk('tenant')->exists($resume->file_path)) {
                Storage::disk('tenant')->delete($resume->file_path);
            }

            // Delete resume and related data (cascade deletes will handle related records)
            $resume->delete();

            return response()->json([
                'success' => true,
                'message' => 'Resume deleted successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Deletion failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Trigger re-analysis of a resume
     */
    public function reanalyze(Request $request, Resume $resume): JsonResponse
    {
        // Ensure user can access this resume
        if ($resume->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Resume not found.',
            ], 404);
        }

        try {
            // Update status to processing
            $resume->update(['status' => 'processing']);

            // Delete existing analysis result to force fresh analysis
            if ($resume->analysisResult) {
                $resume->analysisResult->delete();
            }

            // Dispatch processing job
            ProcessResumeJob::dispatch($resume);

            return response()->json([
                'success' => true,
                'message' => 'Resume is being re-analyzed.',
                'data' => $resume->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Re-analysis failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}