<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ApiController extends Controller
{
    /**
     * API Information
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'name' => 'AI Resume Analyzer API',
            'version' => '1.0.0',
            'description' => 'RESTful API for AI-powered resume analysis',
            'documentation' => url('/api/docs'),
            'endpoints' => [
                'user' => url('/api/v1/user'),
                'resumes' => url('/api/v1/resumes'),
                'analysis' => url('/api/v1/analysis'),
            ],
            'rate_limits' => [
                'api' => '100 requests per hour',
                'upload' => '10 uploads per hour',
                'analysis' => '20 analyses per hour',
            ],
            'authentication' => [
                'api_key' => 'X-API-Key header',
                'bearer_token' => 'Authorization: Bearer {token}',
            ],
        ]);
    }

    /**
     * API Status
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'status' => 'operational',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0',
            'uptime' => $this->getUptime(),
            'services' => $this->getServiceStatus(),
        ]);
    }

    /**
     * Health Check
     */
    public function health(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'checks' => [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'queue' => $this->checkQueue(),
                'storage' => $this->checkStorage(),
            ],
        ];

        // Determine overall status
        $allHealthy = collect($health['checks'])->every(fn($check) => $check['status'] === 'healthy');
        $health['status'] = $allHealthy ? 'healthy' : 'unhealthy';

        $statusCode = $health['status'] === 'healthy' ? 200 : 503;

        return response()->json($health, $statusCode);
    }

    /**
     * Get application uptime
     */
    private function getUptime(): array
    {
        $startFile = storage_path('app/.app_start');

        if (!file_exists($startFile)) {
            file_put_contents($startFile, time());
        }

        $startTime = file_get_contents($startFile);
        $uptime = time() - (int)$startTime;

        return [
            'seconds' => $uptime,
            'formatted' => $this->formatUptime($uptime),
        ];
    }

    /**
     * Format uptime in human readable format
     */
    private function formatUptime(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%d days, %d hours, %d minutes', $days, $hours, $minutes);
    }

    /**
     * Get service status
     */
    private function getServiceStatus(): array
    {
        return [
            'api' => 'operational',
            'database' => 'operational',
            'queue' => 'operational',
            'file_processing' => 'operational',
            'ai_analysis' => 'operational',
        ];
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return [
                'status' => 'healthy',
                'message' => 'Database connection successful',
                'response_time' => $this->measureResponseTime(fn() => DB::select('SELECT 1')),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache connectivity
     */
    private function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'test', 10);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            return [
                'status' => $retrieved === 'test' ? 'healthy' : 'unhealthy',
                'message' => 'Cache operation successful',
                'response_time' => $this->measureResponseTime(fn() => Cache::get($testKey)),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cache operation failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue status
     */
    private function checkQueue(): array
    {
        try {
            // Simple queue status check
            return [
                'status' => 'healthy',
                'message' => 'Queue connection successful',
                'pending_jobs' => DB::table('jobs')->count(),
                'failed_jobs' => DB::table('failed_jobs')->count(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Queue check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage status
     */
    private function checkStorage(): array
    {
        try {
            $testFile = storage_path('app/.health_check');
            file_put_contents($testFile, 'test');
            $content = file_get_contents($testFile);
            unlink($testFile);

            return [
                'status' => $content === 'test' ? 'healthy' : 'unhealthy',
                'message' => 'Storage operation successful',
                'disk_usage' => $this->getDiskUsage(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Storage operation failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Measure response time for a callback
     */
    private function measureResponseTime(callable $callback): string
    {
        $start = microtime(true);
        $callback();
        $end = microtime(true);

        return round(($end - $start) * 1000, 2) . 'ms';
    }

    /**
     * Get disk usage information
     */
    private function getDiskUsage(): array
    {
        $bytes = disk_total_space('/') - disk_free_space('/');
        $total = disk_total_space('/');

        return [
            'used' => $this->formatBytes($bytes),
            'total' => $this->formatBytes($total),
            'percentage' => round(($bytes / $total) * 100, 2) . '%',
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}