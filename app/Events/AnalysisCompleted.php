<?php

namespace App\Events;

use App\Models\Resume;
use App\Models\AnalysisResult;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnalysisCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Resume $resume,
        public AnalysisResult $analysisResult
    ) {}
}
