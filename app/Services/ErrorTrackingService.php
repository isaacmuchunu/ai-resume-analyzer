<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

class ErrorTrackingService
{
    /**
     * Track application error
     */
    public function trackError(Throwable $exception, array $context = []): void
    {
        $errorData = [
            'id' => uniqid('err_'),
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
            'timestamp' => now()->toISOString(),
            'severity' => $this->determineSeverity($exception),
            'fingerprint' => $this->generateFingerprint($exception),
        ];

        // Log to Laravel log system
        Log::error('Application Error Tracked', $errorData);

        // Store in database for analysis
        $this->storeErrorInDatabase($errorData);

        // Send alerts for critical errors
        if ($errorData['severity'] === 'critical') {
            $this->sendCriticalErrorAlert($errorData);
        }
    }

    /**
     * Track performance issues
     */
    public function trackPerformanceIssue(string $operation, float $duration, array $context = []): void
    {
        $performanceData = [
            'operation' => $operation,
            'duration' => $duration,
            'context' => $context,
            'timestamp' => now()->toISOString(),
            'is_slow' => $duration > $this->getSlowThreshold($operation),
        ];

        if ($performanceData['is_slow']) {
            Log::warning('Slow Operation Detected', $performanceData);
            $this->storePerformanceIssue($performanceData);
        }
    }

    /**
     * Track security incidents
     */
    public function trackSecurityIncident(string $type, array $data): void
    {
        $securityData = [
            'type' => $type,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
            'severity' => $this->getSecuritySeverity($type),
        ];

        Log::warning('Security Incident', $securityData);
        $this->storeSecurityIncident($securityData);

        // Send immediate alerts for high severity incidents
        if ($securityData['severity'] === 'high') {
            $this->sendSecurityAlert($securityData);
        }
    }

    /**
     * Get error statistics
     */
    public function getErrorStatistics(int $days = 7): array
    {
        $since = now()->subDays($days);

        return [
            'total_errors' => $this->getErrorCount($since),
            'error_by_type' => $this->getErrorsByType($since),
            'error_by_severity' => $this->getErrorsBySeverity($since),
            'top_error_files' => $this->getTopErrorFiles($since),
            'error_trend' => $this->getErrorTrend($since),
        ];
    }

    /**
     * Determine error severity
     */
    private function determineSeverity(Throwable $exception): string
    {
        $criticalExceptions = [
            'PDOException',
            'Illuminate\Database\QueryException',
            'OutOfMemoryError',
            'Error',
        ];

        $warningExceptions = [
            'Illuminate\Validation\ValidationException',
            'Illuminate\Auth\AuthenticationException',
            'Symfony\Component\HttpKernel\Exception\NotFoundHttpException',
        ];

        $exceptionClass = get_class($exception);

        foreach ($criticalExceptions as $critical) {
            if (str_contains($exceptionClass, $critical)) {
                return 'critical';
            }
        }

        foreach ($warningExceptions as $warning) {
            if (str_contains($exceptionClass, $warning)) {
                return 'warning';
            }
        }

        return 'error';
    }

    /**
     * Generate error fingerprint for grouping
     */
    private function generateFingerprint(Throwable $exception): string
    {
        return md5(
            get_class($exception) .
            $exception->getFile() .
            $exception->getLine() .
            $exception->getMessage()
        );
    }

    /**
     * Store error in database
     */
    private function storeErrorInDatabase(array $errorData): void
    {
        try {
            DB::table('error_logs')->insert([
                'error_id' => $errorData['id'],
                'type' => $errorData['type'],
                'message' => $errorData['message'],
                'file' => $errorData['file'],
                'line' => $errorData['line'],
                'trace' => $errorData['trace'],
                'context' => json_encode($errorData['context']),
                'severity' => $errorData['severity'],
                'fingerprint' => $errorData['fingerprint'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // If we can't store the error, at least log it
            Log::critical('Failed to store error in database', [
                'original_error' => $errorData,
                'storage_error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Store performance issue
     */
    private function storePerformanceIssue(array $performanceData): void
    {
        try {
            DB::table('performance_logs')->insert([
                'operation' => $performanceData['operation'],
                'duration' => $performanceData['duration'],
                'context' => json_encode($performanceData['context']),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store performance issue', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Store security incident
     */
    private function storeSecurityIncident(array $securityData): void
    {
        try {
            DB::table('security_logs')->insert([
                'type' => $securityData['type'],
                'data' => json_encode($securityData['data']),
                'ip_address' => $securityData['ip_address'],
                'user_agent' => $securityData['user_agent'],
                'user_id' => $securityData['user_id'],
                'severity' => $securityData['severity'],
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store security incident', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send critical error alert
     */
    private function sendCriticalErrorAlert(array $errorData): void
    {
        // Implementation would depend on your notification system
        // Could send email, Slack message, SMS, etc.
        Log::critical('CRITICAL ERROR ALERT', $errorData);
    }

    /**
     * Send security alert
     */
    private function sendSecurityAlert(array $securityData): void
    {
        Log::alert('SECURITY ALERT', $securityData);
    }

    /**
     * Get slow threshold for operation
     */
    private function getSlowThreshold(string $operation): float
    {
        return match ($operation) {
            'file_upload' => 30.0,
            'resume_analysis' => 60.0,
            'database_query' => 5.0,
            'api_request' => 10.0,
            default => 15.0,
        };
    }

    /**
     * Get security severity
     */
    private function getSecuritySeverity(string $type): string
    {
        return match ($type) {
            'sql_injection_attempt',
            'xss_attempt',
            'unauthorized_access' => 'high',
            'suspicious_activity',
            'rate_limit_exceeded' => 'medium',
            default => 'low',
        };
    }

    /**
     * Get error count since date
     */
    private function getErrorCount($since): int
    {
        try {
            return DB::table('error_logs')
                ->where('created_at', '>=', $since)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get errors by type
     */
    private function getErrorsByType($since): array
    {
        try {
            return DB::table('error_logs')
                ->where('created_at', '>=', $since)
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->orderByDesc('count')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get errors by severity
     */
    private function getErrorsBySeverity($since): array
    {
        try {
            return DB::table('error_logs')
                ->where('created_at', '>=', $since)
                ->select('severity', DB::raw('count(*) as count'))
                ->groupBy('severity')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get top error files
     */
    private function getTopErrorFiles($since): array
    {
        try {
            return DB::table('error_logs')
                ->where('created_at', '>=', $since)
                ->select('file', DB::raw('count(*) as count'))
                ->groupBy('file')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get error trend (daily counts)
     */
    private function getErrorTrend($since): array
    {
        try {
            return DB::table('error_logs')
                ->where('created_at', '>=', $since)
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('count(*) as count')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
}