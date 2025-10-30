<?php

namespace App\Logging;

use Monolog\Logger;

/**
 * Custom Logger Factory for Application Insights
 * 
 * This class is used by Laravel's logging system to create
 * a custom logger channel for Azure Application Insights
 */
class CreateApplicationInsightsLogger
{
    /**
     * Create a custom Monolog instance with Application Insights handler
     *
     * @param array $config Configuration array from logging.php
     * @return Logger
     */
    public function __invoke(array $config): Logger
    {
        $logger = new Logger('application-insights');
        
        $connectionString = $config['connection_string'] ?? env('APP_INSIGHT_KEY');
        $level = Logger::toMonologLevel($config['level'] ?? 'debug');
        
        if (!empty($connectionString)) {
            $handler = new ApplicationInsightsHandler($connectionString, $level);
            $logger->pushHandler($handler);
        }
        
        return $logger;
    }
}

