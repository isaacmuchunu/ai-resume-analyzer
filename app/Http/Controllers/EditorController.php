<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use App\Models\ResumeSuggestion;
use App\Models\ResumeVersion;
use App\Services\AnthropicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EditorController extends Controller
{
    public function show(Resume $resume)
    {
        $this->authorize('view', $resume);

        $latestVersion = $resume->versions()->latest('version_number')->first();

        return Inertia::render('Resumes/Editor', [
            'resume' => [
                'id' => $resume->id,
                'original_filename' => $resume->original_filename,
            ],
            'content' => $latestVersion?->content ?? '',
            'version' => $latestVersion?->version_number ?? 0,
        ]);
    }

    public function suggest(Request $request, Resume $resume, AnthropicService $anthropic): JsonResponse
    {
        $this->authorize('update', $resume);

        $validated = $request->validate([
            'type' => 'required|in:rewrite,shorten,expand,quantify',
            'text' => 'required|string|max:5000',
            'context' => 'array',
        ]);

        $promptText = $this->buildPrompt($validated['type'], $validated['text']);
        $response = $anthropic->analyzeResume($promptText, []);
        $suggested = $response['analysis_text'] ?? ($response['recommendations'][0] ?? $validated['text']);

        $suggestion = $resume->suggestions()->create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'original_text' => $validated['text'],
            'suggested_text' => $suggested,
            'context' => $validated['context'] ?? [],
        ]);

        return response()->json([
            'success' => true,
            'suggestion' => [
                'id' => $suggestion->id,
                'type' => $suggestion->type,
                'original_text' => $suggestion->original_text,
                'suggested_text' => $suggestion->suggested_text,
                'status' => $suggestion->status,
            ],
        ]);
    }

    public function saveVersion(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        $validated = $request->validate([
            'content' => 'required|string',
            'title' => 'nullable|string|max:255',
        ]);

        $nextVersion = (int) $resume->versions()->max('version_number') + 1;

        $version = $resume->versions()->create([
            'version_number' => $nextVersion,
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
            'metadata' => [
                'saved_by' => $request->user()->id,
            ],
        ]);

        return response()->json([
            'success' => true,
            'version' => [
                'id' => $version->id,
                'version_number' => $version->version_number,
                'title' => $version->title,
                'created_at' => $version->created_at,
            ],
        ]);
    }

    private function buildPrompt(string $type, string $text): string
    {
        return match ($type) {
            'rewrite' => "Rewrite the following resume content to be clearer and more impactful, preserving facts.\n\n$text",
            'shorten' => "Shorten this resume content by ~20% without losing key achievements.\n\n$text",
            'expand' => "Expand this resume content with concrete, quantified achievements.\n\n$text",
            'quantify' => "Suggest quantified improvements (metrics, percentages) to this resume content.\n\n$text",
            default => $text,
        };
    }

    public function versions(Resume $resume): JsonResponse
    {
        $this->authorize('view', $resume);

        $versions = $resume->versions()
            ->orderByDesc('version_number')
            ->limit(50)
            ->get(['id', 'version_number', 'title', 'created_at']);

        return response()->json([
            'success' => true,
            'versions' => $versions,
        ]);
    }

    public function restoreVersion(Request $request, Resume $resume, ResumeVersion $version): JsonResponse
    {
        $this->authorize('update', $resume);

        if ($version->resume_id !== $resume->id) {
            abort(404);
        }

        // Return the content to the client for applying in the editor
        return response()->json([
            'success' => true,
            'content' => $version->content,
            'version' => [
                'id' => $version->id,
                'version_number' => $version->version_number,
                'title' => $version->title,
                'created_at' => $version->created_at,
            ],
        ]);
    }
}


