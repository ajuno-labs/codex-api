<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    /**
     * Health check endpoint
     * Returns overall system status and component health
     */
    public function check(): JsonResponse
    {
        $status = 'healthy';
        $checks = [];
        $timestamp = now()->toISOString();

        // Database health check
        $dbHealth = $this->checkDatabase();
        $checks['database'] = $dbHealth;
        if (!$dbHealth['healthy']) {
            $status = 'unhealthy';
        }

        // Cache health check
        $cacheHealth = $this->checkCache();
        $checks['cache'] = $cacheHealth;
        if (!$cacheHealth['healthy']) {
            $status = 'degraded';
        }

        // Application health check
        $appHealth = $this->checkApplication();
        $checks['application'] = $appHealth;
        if (!$appHealth['healthy']) {
            $status = 'unhealthy';
        }

        $response = [
            'status' => $status,
            'timestamp' => $timestamp,
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
            'checks' => $checks,
        ];

        // Return appropriate HTTP status code
        $httpStatus = match ($status) {
            'healthy' => 200,
            'degraded' => 200,
            'unhealthy' => 503,
            default => 500,
        };

        return response()->json($response, $httpStatus);
    }

    /**
     * Simple health ping endpoint
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'message' => 'pong',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            // Test a simple query
            DB::select('SELECT 1');

            return [
                'healthy' => true,
                'message' => 'Database connection successful',
                'response_time_ms' => $responseTime,
            ];
        } catch (\Exception $e) {
            Log::error('Database health check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'healthy' => false,
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache functionality
     */
    private function checkCache(): array
    {
        try {
            $start = microtime(true);
            $testKey = 'health_check_' . uniqid();
            $testValue = 'test_value';

            // Test cache write
            Cache::put($testKey, $testValue, 60);

            // Test cache read
            $retrievedValue = Cache::get($testKey);

            // Clean up
            Cache::forget($testKey);

            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($retrievedValue === $testValue) {
                return [
                    'healthy' => true,
                    'message' => 'Cache is working properly',
                    'response_time_ms' => $responseTime,
                ];
            } else {
                return [
                    'healthy' => false,
                    'message' => 'Cache read/write test failed',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Cache health check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'healthy' => false,
                'message' => 'Cache functionality failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check application-specific health
     */
    private function checkApplication(): array
    {
        try {
            $checks = [];
            $allHealthy = true;

            // Check if required environment variables are set
            $requiredEnvVars = ['APP_KEY', 'DB_CONNECTION', 'JWT_SECRET'];
            foreach ($requiredEnvVars as $envVar) {
                if (!env($envVar)) {
                    $checks['env_vars'][] = "$envVar is not set";
                    $allHealthy = false;
                }
            }

            // Check disk space (basic check)
            $diskFree = disk_free_space(storage_path());
            $diskTotal = disk_total_space(storage_path());
            $diskUsagePercent = round((($diskTotal - $diskFree) / $diskTotal) * 100, 2);

            $checks['disk_usage'] = [
                'usage_percent' => $diskUsagePercent,
                'free_bytes' => $diskFree,
                'total_bytes' => $diskTotal,
            ];

            // Warn if disk usage is high
            if ($diskUsagePercent > 90) {
                $checks['disk_warning'] = 'Disk usage is high';
                $allHealthy = false;
            }

            return [
                'healthy' => $allHealthy,
                'message' => $allHealthy ? 'Application checks passed' : 'Some application checks failed',
                'details' => $checks,
            ];
        } catch (\Exception $e) {
            Log::error('Application health check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'healthy' => false,
                'message' => 'Application health check failed',
                'error' => $e->getMessage(),
            ];
        }
    }
}
