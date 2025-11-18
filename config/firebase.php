<?php

declare(strict_types=1);

return [
    /*
     * ------------------------------------------------------------------------
     * Default Firebase project
     * ------------------------------------------------------------------------
     */

    //    'default' => env('FIREBASE_PROJECT', 'app'),

    /*
     * ------------------------------------------------------------------------
     * Firebase project configurations
     * ------------------------------------------------------------------------
     */

    // 'projects' => [
    //     'app' => [

    //         'project_id' => env('FIREBASE_PROJECT_ID'),

    //         'credentials' => (static function () {
    //             $json = env('FIREBASE_CREDENTIALS_JSON');
    //             if (!empty($json)) {
    //                 $decoded = json_decode(base64_decode($json, true) ?: $json, true);
    //                 if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
    //                     return ['json' => $decoded];
    //                 }
    //             }

    //             return [
    //                 'file' => env('FIREBASE_CREDENTIALS', env('GOOGLE_APPLICATION_CREDENTIALS')),
    //             ];
    //         })(),

    //         /*
    //          * ------------------------------------------------------------------------
    //          * Firebase Auth Component
    //          * ------------------------------------------------------------------------
    //          */

    //         //            'auth' => [
    //         //                'tenant_id' => env('FIREBASE_AUTH_TENANT_ID'),
    //         //            ],
    //         //
    //         //            /*
    //         //             * ------------------------------------------------------------------------
    //         //             * Firestore Component
    //         //             * ------------------------------------------------------------------------
    //         //             */
    //         //
    //         //            'firestore' => [
    //         //
    //         //                /*
    //         //                 * If you want to access a Firestore database other than the default database,
    //         //                 * enter its name here.
    //         //                 *
    //         //                 * By default, the Firestore client will connect to the `(default)` database.
    //         //                 *
    //         //                 * https://firebase.google.com/docs/firestore/manage-databases
    //         //                 */
    //         //
    //         //                // 'database' => env('FIREBASE_FIRESTORE_DATABASE'),
    //         //            ],
    //         //
    //         //            /*
    //         //             * ------------------------------------------------------------------------
    //         //             * Firebase Realtime Database
    //         //             * ------------------------------------------------------------------------
    //         //             */
    //         //
    //         //            'database' => [
    //         //
    //         //                /*
    //         //                 * In most of the cases the project ID defined in the credentials file
    //         //                 * determines the URL of your project's Realtime Database. If the
    //         //                 * connection to the Realtime Database fails, you can override
    //         //                 * its URL with the value you see at
    //         //                 *
    //         //                 * https://console.firebase.google.com/u/1/project/_/database
    //         //                 *
    //         //                 * Please make sure that you use a full URL like, for example,
    //         //                 * https://my-project-id.firebaseio.com
    //         //                 */
    //         //
    //         //                'url' => env('FIREBASE_DATABASE_URL'),
    //         //
    //         //                /*
    //         //                 * As a best practice, a service should have access to only the resources it needs.
    //         //                 * To get more fine-grained control over the resources a Firebase app instance can access,
    //         //                 * use a unique identifier in your Security Rules to represent your service.
    //         //                 *
    //         //                 * https://firebase.google.com/docs/database/admin/start#authenticate-with-limited-privileges
    //         //                 */
    //         //
    //         //                // 'auth_variable_override' => [
    //         //                //     'uid' => 'my-service-worker'
    //         //                // ],
    //         //
    //         //            ],
    //         //
    //         //            'dynamic_links' => [
    //         //
    //         //                /*
    //         //                 * Dynamic links can be built with any URL prefix registered on
    //         //                 *
    //         //                 * https://console.firebase.google.com/u/1/project/_/durablelinks/links/
    //         //                 *
    //         //                 * You can define one of those domains as the default for new Dynamic
    //         //                 * Links created within your project.
    //         //                 *
    //         //                 * The value must be a valid domain, for example,
    //         //                 * https://example.page.link
    //         //                 */
    //         //
    //         //                'default_domain' => env('FIREBASE_DYNAMIC_LINKS_DEFAULT_DOMAIN'),
    //         //            ],
    //         //
    //         //            /*
    //         //             * ------------------------------------------------------------------------
    //         //             * Firebase Cloud Storage
    //         //             * ------------------------------------------------------------------------
    //         //             */
    //         //
    //         //            'storage' => [
    //         //
    //         //                /*
    //         //                 * Your project's default storage bucket usually uses the project ID
    //         //                 * as its name. If you have multiple storage buckets and want to
    //         //                 * use another one as the default for your application, you can
    //         //                 * override it here.
    //         //                 */
    //         //
    //         //                'default_bucket' => env('FIREBASE_STORAGE_DEFAULT_BUCKET'),
    //         //
    //         //            ],
    //         //
    //         //            /*
    //         //             * ------------------------------------------------------------------------
    //         //             * Caching
    //         //             * ------------------------------------------------------------------------
    //         //             *
    //         //             * The Firebase Admin SDK can cache some data returned from the Firebase
    //         //             * API, for example Google's public keys used to verify ID tokens.
    //         //             *
    //         //             */
    //         //
    //         //            'cache_store' => env('FIREBASE_CACHE_STORE', 'file'),
    //         //
    //         //            /*
    //         //             * ------------------------------------------------------------------------
    //         //             * Logging
    //         //             * ------------------------------------------------------------------------
    //         //             *
    //         //             * Enable logging of HTTP interaction for insights and/or debugging.
    //         //             *
    //         //             * Log channels are defined in config/logging.php
    //         //             *
    //         //             * Successful HTTP messages are logged with the log level 'info'.
    //         //             * Failed HTTP messages are logged with the log level 'notice'.
    //         //             *
    //         //             * Note: Using the same channel for simple and debug logs will result in
    //         //             * two entries per request and response.
    //         //             */
    //         //
    //         //            'logging' => [
    //         //                'http_log_channel' => env('FIREBASE_HTTP_LOG_CHANNEL'),
    //         //                'http_debug_log_channel' => env('FIREBASE_HTTP_DEBUG_LOG_CHANNEL'),
    //         //            ],
    //         //
    //         //            /*
    //         //             * ------------------------------------------------------------------------
    //         //             * HTTP Client Options
    //         //             * ------------------------------------------------------------------------
    //         //             *
    //         //             * Behavior of the HTTP Client performing the API requests
    //         //             */
    //         //
    //         //            'http_client_options' => [
    //         //
    //         //                /*
    //         //                 * Use a proxy that all API requests should be passed through.
    //         //                 * (default: none)
    //         //                 */
    //         //
    //         //                'proxy' => env('FIREBASE_HTTP_CLIENT_PROXY'),
    //         //
    //         //                /*
    //         //                 * Set the maximum amount of seconds (float) that can pass before
    //         //                 * a request is considered timed out
    //         //                 *
    //         //                 * The default time out can be reviewed at
    //         //                 * https://github.com/kreait/firebase-php/blob/6.x/src/Firebase/Http/HttpClientOptions.php
    //         //                 */
    //         //
    //         //                'timeout' => env('FIREBASE_HTTP_CLIENT_TIMEOUT'),
    //         //
    //         //                'guzzle_middlewares' => [],
    //         //            ],
    //     ],
    // ],
    'projects' => [
        'app' => [
            'project_id' => env('FIREBASE_PROJECT_ID'),

            'credentials' => (static function () {
                // Priority 1: Individual environment variables (recommended for production/DigitalOcean)
                if (env('FIREBASE_CLIENT_EMAIL')) {
                    return [
                        'json' => [
                            'type' => env('FIREBASE_TYPE', 'service_account'),
                            'project_id' => env('FIREBASE_PROJECT_ID'),
                            'private_key_id' => env('FIREBASE_PRIVATE_KEY_ID'),
                            'private_key' => str_replace('\\n', "\n", env('FIREBASE_PRIVATE_KEY')),
                            'client_email' => env('FIREBASE_CLIENT_EMAIL'),
                            'client_id' => env('FIREBASE_CLIENT_ID'),
                            'auth_uri' => env('FIREBASE_AUTH_URI', 'https://accounts.google.com/o/oauth2/auth'),
                            'token_uri' => env('FIREBASE_TOKEN_URI', 'https://oauth2.googleapis.com/token'),
                            'auth_provider_x509_cert_url' => env('FIREBASE_AUTH_PROVIDER_X509_CERT_URL', 'https://www.googleapis.com/oauth2/v1/certs'),
                            'client_x509_cert_url' => env('FIREBASE_CLIENT_X509_CERT_URL'),
                            'universe_domain' => env('FIREBASE_UNIVERSE_DOMAIN', 'googleapis.com'),
                        ]
                    ];
                }

                // Priority 2: Base64 encoded JSON (alternative for some deployment platforms)
                $base64Json = env('FIREBASE_CREDENTIALS_BASE64');
                if (!empty($base64Json)) {
                    $decoded = json_decode(base64_decode($base64Json), true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        return ['json' => $decoded];
                    }
                }

                // Priority 3: JSON string (from FIREBASE_CREDENTIALS_JSON variable)
                $jsonString = env('FIREBASE_CREDENTIALS_JSON');
                if (!empty($jsonString)) {
                    $decoded = json_decode($jsonString, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        return ['json' => $decoded];
                    }
                }

                // Priority 4: File path (for local development only)
                $credentialsPath = env('FIREBASE_CREDENTIALS', env('GOOGLE_APPLICATION_CREDENTIALS'));
                if (!empty($credentialsPath)) {
                    // Check if it's an absolute path
                    $fullPath = $credentialsPath;
                    
                    // If relative path, convert to absolute from base_path
                    if (!file_exists($fullPath)) {
                        $fullPath = base_path($credentialsPath);
                    }
                    
                    if (file_exists($fullPath)) {
                        return ['file' => $fullPath];
                    }
                }

                throw new \Exception('Firebase credentials not configured. Please set FIREBASE_CLIENT_EMAIL or FIREBASE_CREDENTIALS in environment variables, or ensure the credentials file exists at the specified path.');
            })(),
        ],
    ],
];
