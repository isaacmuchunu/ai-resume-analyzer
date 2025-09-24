<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResumeUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $maxSize = config('app.max_file_size', 10240); // 10MB default

        return [
            'file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,txt',
                "max:{$maxSize}",
            ],
            'target_role' => 'nullable|string|max:255',
            'target_industry' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a resume file to upload.',
            'file.file' => 'The uploaded file is not valid.',
            'file.mimes' => 'Only PDF, DOC, DOCX, and TXT files are allowed.',
            'file.max' => 'The file size must not exceed 10MB.',
            'target_role.max' => 'The target role field must not exceed 255 characters.',
            'target_industry.max' => 'The target industry field must not exceed 255 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Clean up optional fields
        if ($this->target_role === '') {
            $this->merge(['target_role' => null]);
        }

        if ($this->target_industry === '') {
            $this->merge(['target_industry' => null]);
        }
    }
}