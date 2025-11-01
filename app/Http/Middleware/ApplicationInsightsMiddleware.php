<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Application Insights Request Tracking Middleware
 * 
 * Tracks HTTP requests, performance metrics, and sends telemetry to Azure Application Insights
 */
class ApplicationInsightsMiddleware
{
    /**
     * Handle an incoming request and track it in Application Insights
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if no connection string configured (support both env variable names)
        $connectionString = env('APPLICATIONINSIGHTS_CONNECTION_STRING') ?? env('APP_INSIGHT_KEY');
        if (empty($connectionString)) {
            return $next($request);
        }

        // Generate unique operation ID
        $operationId = Str::uuid()->toString();
        $request->headers->set('X-Request-ID', $operationId);
        
        $startTime = microtime(true);
        $startDateTime = now();

        try {
            // Process request
            $response = $next($request);
            
            // Calculate duration
            $duration = (microtime(true) - $startTime) * 1000; // milliseconds
            
            // Track request after response is ready
            $this->trackRequest($request, $response, $startDateTime, $duration, $operationId, true);
            
            return $response;
        } catch (\Throwable $exception) {
            // Calculate duration
            $duration = (microtime(true) - $startTime) * 1000;
            
            // Track failed request
            $this->trackRequest($request, null, $startDateTime, $duration, $operationId, false);
            
            // Re-throw exception
            throw $exception;
        }
    }

    /**
     * Track request to Application Insights
     *
     * @param Request $request
     * @param Response|null $response
     * @param \Illuminate\Support\Carbon $startTime
     * @param float $duration Duration in milliseconds
     * @param string $operationId
     * @param bool $success
     */
    private function trackRequest(
        Request $request,
        ?Response $response,
        $startTime,
        float $duration,
        string $operationId,
        bool $success
    ): void {
        try {
            // Support both Azure standard and legacy env variable names
            $connectionString = env('APPLICATIONINSIGHTS_CONNECTION_STRING') ?? env('APP_INSIGHT_KEY');
            $config = $this->parseConnectionString($connectionString);
            
            $telemetry = [
                'name' => 'Microsoft.ApplicationInsights.Request',
                'time' => $startTime->format('c'),
                'iKey' => $config['instrumentationKey'],
                'tags' => [
                    'ai.cloud.role' => config('app.name', 'Laravel'),
                    'ai.cloud.roleInstance' => gethostname(),
                    'ai.operation.id' => $operationId,
                    'ai.operation.name' => $this->getOperationName($request),
                    'ai.user.id' => auth()->id() ?? 'anonymous',
                    'ai.session.id' => session()->getId(),
                ],
                'data' => [
                    'baseType' => 'RequestData',
                    'baseData' => [
                        'ver' => 2,
                        'id' => $operationId,
                        'name' => $this->getOperationName($request),
                        'duration' => $this->formatDuration($duration),
                        'responseCode' => $response ? (string) $response->getStatusCode() : '500',
                        'success' => $success,
                        'url' => $request->fullUrl(),
                        'properties' => [
                            'method' => $request->method(),
                            'path' => $request->path(),
                            'ip' => $request->ip(),
                            'user_agent' => $request->userAgent(),
                            'content_type' => $request->header('Content-Type'),
                            'route' => $request->route() ? $request->route()->getName() : null,
                        ],
                        'measurements' => [
                            'duration_ms' => round($duration, 2),
                        ],
                    ],
                ],
            ];

            // Send asynchronously (don't wait for response)
            $this->sendTelemetry($config['ingestionEndpoint'], $telemetry);
        } catch (\Exception $e) {
            // Silently fail - don't break application
            // Uncomment for debugging:
            // \Log::error('Failed to send Application Insights telemetry: ' . $e->getMessage());
        }
    }

    /**
     * Get human-readable operation name from request
     *
     * @param Request $request
     * @return string
     */
    private function getOperationName(Request $request): string
    {
        // Try to get route name first
        if ($request->route() && $request->route()->getName()) {
            return $request->method() . ' ' . $request->route()->getName();
        }

        // Fallback to method + path
        return $request->method() . ' ' . $request->path();
    }

    /**
     * Format duration to ISO 8601 format required by Application Insights
     *
     * @param float $milliseconds
     * @return string
     */
    private function formatDuration(float $milliseconds): string
    {
        $seconds = floor($milliseconds / 1000);
        $ms = $milliseconds % 1000;
        
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        return sprintf('P0DT%dH%dM%d.%03dS', $hours, $minutes, $secs, $ms);
    }

    /**
     * Parse Azure connection string
     *
     * @param string $connectionString
     * @return array
     */
    private function parseConnectionString(string $connectionString): array
    {
        $config = [
            'instrumentationKey' => null,
            'ingestionEndpoint' => 'https://dc.services.visualstudio.com',
        ];

        $parts = explode(';', $connectionString);
        
        foreach ($parts as $part) {
            if (str_starts_with($part, 'InstrumentationKey=')) {
                $config['instrumentationKey'] = substr($part, 19);
            } elseif (str_starts_with($part, 'IngestionEndpoint=')) {
                $config['ingestionEndpoint'] = rtrim(substr($part, 18), '/');
            }
        }

        return $config;
    }

    /**
     * Send telemetry to Application Insights asynchronously
     *
     * @param string $endpoint
     * @param array $telemetry
     */
    private function sendTelemetry(string $endpoint, array $telemetry): void
    {
        $url = $endpoint . '/v2.1/track';

        // Send async - don't wait for response
        Http::timeout(3)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $telemetry);
    }
}

