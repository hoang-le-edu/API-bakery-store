<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Http;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Track exception to Application Insights
            // For testing: enable on local by setting ENABLE_APPINSIGHTS=true in .env
            $shouldTrack = config('app.env') === 'production' 
                || config('app.env') === 'staging'
                || env('ENABLE_APPINSIGHTS', false);
                
            if ($shouldTrack) {
                $this->trackExceptionToAppInsights($e);
            }
        });
    }

    /**
     * Send exception telemetry to Application Insights
     */
    protected function trackExceptionToAppInsights(Throwable $exception): void
    {
        try {
            $connectionString = env('APPLICATIONINSIGHTS_CONNECTION_STRING') ?? env('APP_INSIGHT_KEY');
            
            if (empty($connectionString)) {
                return;
            }

            $config = $this->parseConnectionString($connectionString);
            
            $telemetry = [
                'name' => 'Microsoft.ApplicationInsights.Exception',
                'time' => now()->format('c'),
                'iKey' => $config['instrumentationKey'],
                'tags' => [
                    'ai.cloud.role' => config('app.name', 'Laravel'),
                    'ai.cloud.roleInstance' => gethostname(),
                    'ai.user.id' => auth()->id() ?? 'anonymous',
                ],
                'data' => [
                    'baseType' => 'ExceptionData',
                    'baseData' => [
                        'ver' => 2,
                        'exceptions' => [
                            [
                                'typeName' => get_class($exception),
                                'message' => $exception->getMessage(),
                                'hasFullStack' => true,
                                'stack' => $exception->getTraceAsString(),
                            ],
                        ],
                        'properties' => [
                            'environment' => config('app.env'),
                            'url' => request()->fullUrl(),
                            'method' => request()->method(),
                            'ip' => request()->ip(),
                            'user_id' => auth()->id(),
                            'file' => $exception->getFile(),
                            'line' => $exception->getLine(),
                        ],
                    ],
                ],
            ];

            $url = $config['ingestionEndpoint'] . '/v2.1/track';
            Http::timeout(3)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $telemetry);
        } catch (\Exception $e) {
            // Silently fail - don't break application
        }
    }

    /**
     * Parse Azure connection string
     */
    protected function parseConnectionString(string $connectionString): array
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
}
