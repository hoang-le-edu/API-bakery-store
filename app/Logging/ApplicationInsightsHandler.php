<?php

namespace App\Logging;

use Illuminate\Support\Facades\Http;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Monolog\Logger;

/**
 * Custom Monolog Handler for Azure Application Insights
 * 
 * Sends logs to Azure Application Insights via REST API
 * Supports traces, exceptions, and custom events
 */
class ApplicationInsightsHandler extends AbstractProcessingHandler
{
    /**
     * @var string Application Insights Instrumentation Key
     */
    private $instrumentationKey;

    /**
     * @var string Application Insights Ingestion Endpoint
     */
    private $ingestionEndpoint;

    /**
     * @var string Application name/role
     */
    private $roleName;

    /**
     * Constructor
     *
     * @param string $connectionString Azure Application Insights connection string
     * @param int $level Minimum log level
     * @param bool $bubble Whether to bubble to next handler
     */
    public function __construct(string $connectionString, int $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        
        $this->parseConnectionString($connectionString);
        $this->roleName = config('app.name', 'Laravel');
    }

    /**
     * Parse Azure connection string to extract key and endpoint
     *
     * @param string $connectionString
     */
    private function parseConnectionString(string $connectionString): void
    {
        $parts = explode(';', $connectionString);
        
        foreach ($parts as $part) {
            if (str_starts_with($part, 'InstrumentationKey=')) {
                $this->instrumentationKey = substr($part, 19);
            } elseif (str_starts_with($part, 'IngestionEndpoint=')) {
                $this->ingestionEndpoint = rtrim(substr($part, 18), '/');
            }
        }

        // Default endpoint if not specified
        if (empty($this->ingestionEndpoint)) {
            $this->ingestionEndpoint = 'https://dc.services.visualstudio.com';
        }
    }

    /**
     * Write log record to Application Insights
     *
     * @param LogRecord $record
     */
    protected function write(LogRecord $record): void
    {
        if (empty($this->instrumentationKey)) {
            return; // Skip if no instrumentation key
        }

        try {
            $telemetryItem = $this->buildTelemetryItem($record);
            $this->sendToApplicationInsights($telemetryItem);
        } catch (\Exception $e) {
            // Don't throw exceptions from logger - just silently fail
            // You can uncomment this for debugging:
            // error_log("Failed to send log to Application Insights: " . $e->getMessage());
        }
    }

    /**
     * Build telemetry item from log record
     *
     * @param LogRecord $record
     * @return array
     */
    private function buildTelemetryItem(LogRecord $record): array
    {
        $context = $record->context;
        $isException = isset($context['exception']) && $context['exception'] instanceof \Throwable;

        // Determine if this is an exception or trace
        $baseType = $isException ? 'ExceptionData' : 'MessageData';
        $baseData = $isException 
            ? $this->buildExceptionData($context['exception'], $record)
            : $this->buildTraceData($record);

        return [
            'name' => $isException ? 'Microsoft.ApplicationInsights.Exception' : 'Microsoft.ApplicationInsights.Message',
            'time' => $record->datetime->format('c'),
            'iKey' => $this->instrumentationKey,
            'tags' => [
                'ai.cloud.role' => $this->roleName,
                'ai.cloud.roleInstance' => gethostname(),
                'ai.operation.name' => request()->path() ?? 'CLI',
                'ai.operation.id' => request()->header('X-Request-ID') ?? uniqid(),
            ],
            'data' => [
                'baseType' => $baseType,
                'baseData' => $baseData,
            ],
        ];
    }

    /**
     * Build trace (message) data
     *
     * @param LogRecord $record
     * @return array
     */
    private function buildTraceData(LogRecord $record): array
    {
        return [
            'ver' => 2,
            'message' => $record->message,
            'severityLevel' => $this->mapLogLevel($record->level->value),
            'properties' => array_merge(
                $this->getRequestContext(),
                $record->context
            ),
        ];
    }

    /**
     * Build exception data
     *
     * @param \Throwable $exception
     * @param LogRecord $record
     * @return array
     */
    private function buildExceptionData(\Throwable $exception, LogRecord $record): array
    {
        $exceptionDetails = [
            'id' => 1,
            'outerId' => 0,
            'typeName' => get_class($exception),
            'message' => $exception->getMessage(),
            'hasFullStack' => true,
            'parsedStack' => $this->parseStackTrace($exception),
        ];

        return [
            'ver' => 2,
            'exceptions' => [$exceptionDetails],
            'severityLevel' => $this->mapLogLevel($record->level->value),
            'properties' => array_merge(
                $this->getRequestContext(),
                [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'code' => $exception->getCode(),
                ]
            ),
        ];
    }

    /**
     * Parse exception stack trace
     *
     * @param \Throwable $exception
     * @return array
     */
    private function parseStackTrace(\Throwable $exception): array
    {
        $frames = [];
        $trace = $exception->getTrace();

        foreach ($trace as $index => $frame) {
            $frames[] = [
                'level' => $index,
                'method' => ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? ''),
                'assembly' => $frame['file'] ?? 'Unknown',
                'fileName' => $frame['file'] ?? 'Unknown',
                'line' => $frame['line'] ?? 0,
            ];
        }

        return $frames;
    }

    /**
     * Get current request context
     *
     * @return array
     */
    private function getRequestContext(): array
    {
        if (!app()->runningInConsole()) {
            return [
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'user_id' => auth()->id() ?? null,
            ];
        }

        return [
            'environment' => app()->environment(),
            'command' => implode(' ', $_SERVER['argv'] ?? []),
        ];
    }

    /**
     * Map Monolog log level to Application Insights severity level
     *
     * @param int $level Monolog level
     * @return int Application Insights severity (0=Verbose, 1=Information, 2=Warning, 3=Error, 4=Critical)
     */
    private function mapLogLevel(int $level): int
    {
        return match ($level) {
            Logger::DEBUG => 0,      // Verbose
            Logger::INFO => 1,       // Information
            Logger::NOTICE => 1,     // Information
            Logger::WARNING => 2,    // Warning
            Logger::ERROR => 3,      // Error
            Logger::CRITICAL => 4,   // Critical
            Logger::ALERT => 4,      // Critical
            Logger::EMERGENCY => 4,  // Critical
            default => 1,
        };
    }

    /**
     * Send telemetry to Application Insights
     *
     * @param array $telemetryItem
     */
    private function sendToApplicationInsights(array $telemetryItem): void
    {
        $url = $this->ingestionEndpoint . '/v2.1/track';

        Http::timeout(5)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($url, $telemetryItem);
    }
}

