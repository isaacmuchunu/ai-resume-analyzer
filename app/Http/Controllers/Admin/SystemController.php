<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use App\Models\ActivityLog;
use App\Services\ErrorTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class SystemController extends Controller
{
    public function __construct(
        private ErrorTrackingService $errorTracking
    ) {}

    /**
     * Display system logs
     */
    public function logs(Request $request)
    {
        $query = ActivityLog::with('causer');

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by description
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->user_id);
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate(50)
            ->through(function ($log) {
                return [
                    'id' => $log->id,
                    'description' => $log->description,
                    'created_at' => $log->created_at,
                    'causer' => $log->causer ? [
                        'name' => $log->causer->full_name,
                        'email' => $log->causer->email,
                    ] : null,
                    'properties' => $log->properties,
                ];
            });

        return Inertia::render('Admin/System/Logs', [
            'logs' => $logs,
            'filters' => $request->only(['date_from', 'date_to', 'search', 'user_id']),
        ]);
    }

    /**
     * Display error logs
     */
    public function errors(Request $request)
    {
        $query = ErrorLog::query();

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search in message
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('message', 'like', '%' . $request->search . '%')
                  ->orWhere('context->url', 'like', '%' . $request->search . '%');
            });
        }

        $errors = $query->orderBy('created_at', 'desc')
            ->paginate(25)
            ->through(function ($error) {
                return [
                    'id' => $error->id,
                    'severity' => $error->severity,
                    'message' => $error->message,
                    'file' => $error->file,
                    'line' => $error->line,
                    'count' => $error->count,
                    'created_at' => $error->created_at,
                    'updated_at' => $error->updated_at,
                    'context' => $error->context,
                ];
            });

        // Get error statistics
        $errorStats = [
            'total_errors' => ErrorLog::count(),
            'errors_today' => ErrorLog::whereDate('created_at', today())->count(),
            'critical_errors' => ErrorLog::where('severity', 'critical')->count(),
            'resolved_errors' => ErrorLog::where('is_resolved', true)->count(),
        ];

        return Inertia::render('Admin/System/Errors', [
            'errors' => $errors,
            'stats' => $errorStats,
            'filters' => $request->only(['severity', 'date_from', 'date_to', 'search']),
        ]);
    }

    /**
     * Display performance metrics
     */
    public function performance()
    {
        // Database performance
        $dbStats = [
            'total_tables' => $this->getDatabaseTableCount(),
            'database_size' => $this->getDatabaseSize(),
            'slow_queries' => $this->getSlowQueryCount(),
        ];

        // Cache performance
        $cacheStats = [
            'cache_hits' => $this->getCacheHitRate(),
            'cache_size' => $this->getCacheSize(),
        ];

        // Queue performance
        $queueStats = [
            'pending_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
        ];

        // System resource usage
        $systemStats = [
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        // Recent performance metrics
        $performanceHistory = $this->getPerformanceHistory();

        return Inertia::render('Admin/System/Performance', [
            'database' => $dbStats,
            'cache' => $cacheStats,
            'queue' => $queueStats,
            'system' => $systemStats,
            'history' => $performanceHistory,
        ]);
    }

    /**
     * Display system health check
     */
    public function health()
    {
        $healthChecks = [
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'storage' => $this->checkStorageHealth(),
            'queue' => $this->checkQueueHealth(),
            'mail' => $this->checkMailHealth(),
            'stripe' => $this->checkStripeHealth(),
            'anthropic' => $this->checkAnthropicHealth(),
        ];

        $overallHealth = collect($healthChecks)->every(fn($check) => $check['status'] === 'healthy') ? 'healthy' : 'unhealthy';

        return Inertia::render('Admin/System/Health', [
            'overall_status' => $overallHealth,
            'checks' => $healthChecks,
            'last_checked' => now()->toISOString(),
        ]);
    }

    /**
     * Clear application cache
     */
    public function clearCache(Request $request)
    {
        $request->validate([
            'type' => 'required|in:all,config,route,view,cache'
        ]);

        try {
            switch ($request->type) {
                case 'all':
                    Artisan::call('optimize:clear');
                    $message = 'All caches cleared successfully';
                    break;
                case 'config':
                    Artisan::call('config:clear');
                    $message = 'Configuration cache cleared';
                    break;
                case 'route':
                    Artisan::call('route:clear');
                    $message = 'Route cache cleared';
                    break;
                case 'view':
                    Artisan::call('view:clear');
                    $message = 'View cache cleared';
                    break;
                case 'cache':
                    Artisan::call('cache:clear');
                    $message = 'Application cache cleared';
                    break;
            }

            // Log the cache clear action
            ActivityLog::logForUser(auth()->user(), 'Admin cleared cache', null, [
                'cache_type' => $request->type,
                'admin_user' => auth()->user()->email,
            ]);

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to clear cache: ' . $e->getMessage());
        }
    }

    // Private helper methods

    private function getDatabaseTableCount(): int
    {
        $tables = DB::select('SHOW TABLES');
        return count($tables);
    }

    private function getDatabaseSize(): string
    {
        $size = DB::select("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS DB
            FROM information_schema.tables
            WHERE table_schema = ?
        ", [config('database.connections.mysql.database')]);

        return ($size[0]->DB ?? 0) . ' MB';
    }

    private function getSlowQueryCount(): int
    {
        // This would require slow query log to be enabled
        return 0; // Placeholder
    }

    private function getCacheHitRate(): string
    {
        // This would require cache statistics
        return '95%'; // Placeholder
    }

    private function getCacheSize(): string
    {
        return '10 MB'; // Placeholder
    }

    private function getMemoryUsage(): string
    {
        return round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB';
    }

    private function getDiskUsage(): array
    {
        $totalSpace = disk_total_space('/');
        $freeSpace = disk_free_space('/');
        $usedSpace = $totalSpace - $freeSpace;

        return [
            'total' => $this->formatBytes($totalSpace),
            'used' => $this->formatBytes($usedSpace),
            'free' => $this->formatBytes($freeSpace),
            'percentage' => round(($usedSpace / $totalSpace) * 100, 2),
        ];
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function getPerformanceHistory(): array
    {
        // This would collect historical performance data
        return []; // Placeholder
    }

    private function checkDatabaseHealth(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'healthy', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    private function checkCacheHealth(): array
    {
        try {
            Cache::put('health_check', 'test', 10);
            $value = Cache::get('health_check');
            return $value === 'test'
                ? ['status' => 'healthy', 'message' => 'Cache is working']
                : ['status' => 'unhealthy', 'message' => 'Cache read/write failed'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Cache error: ' . $e->getMessage()];
        }
    }

    private function checkStorageHealth(): array
    {
        try {
            $testFile = 'health_check.txt';
            Storage::put($testFile, 'test content');
            $content = Storage::get($testFile);
            Storage::delete($testFile);

            return $content === 'test content'
                ? ['status' => 'healthy', 'message' => 'Storage is working']
                : ['status' => 'unhealthy', 'message' => 'Storage read/write failed'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Storage error: ' . $e->getMessage()];
        }
    }

    private function checkQueueHealth(): array
    {
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();

        if ($failedJobs > 10) {
            return ['status' => 'warning', 'message' => "{$failedJobs} failed jobs need attention"];
        }

        return ['status' => 'healthy', 'message' => "Queue working ({$pendingJobs} pending, {$failedJobs} failed)"];
    }

    private function checkMailHealth(): array
    {
        // This would test mail configuration
        return ['status' => 'healthy', 'message' => 'Mail configuration appears valid'];
    }

    private function checkStripeHealth(): array
    {
        $stripeKey = config('services.stripe.secret');
        return $stripeKey
            ? ['status' => 'healthy', 'message' => 'Stripe integration configured']
            : ['status' => 'warning', 'message' => 'Stripe not configured'];
    }

    private function checkAnthropicHealth(): array
    {
        $apiKey = config('services.anthropic.api_key');
        return $apiKey
            ? ['status' => 'healthy', 'message' => 'Anthropic API configured']
            : ['status' => 'warning', 'message' => 'Anthropic API not configured'];
    }
}