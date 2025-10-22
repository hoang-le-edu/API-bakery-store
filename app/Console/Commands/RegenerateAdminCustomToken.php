<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Kreait\Firebase\Factory;

class RegenerateAdminCustomToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:regenerate-token {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate Firebase custom token for admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        // If email not provided, prompt for it
        if (!$email) {
            $email = $this->ask('Enter admin email');
        }

        // Find user
        $user = User::where('email', $email)
            ->where('is_admin', 1)
            ->first();

        if (!$user) {
            $this->error("Admin user with email {$email} not found!");
            return Command::FAILURE;
        }

        if (!$user->firebase_uid) {
            $this->error("User does not have firebase_uid!");
            return Command::FAILURE;
        }

        try {
            // Get Firebase credentials from config
            $credentials = config('firebase.projects.app.credentials');
            
            if (empty($credentials)) {
                $this->error("Firebase credentials not found!");
                $this->warn("Please set one of the following in your .env:");
                $this->line("  FIREBASE_CREDENTIALS=/path/to/service-account.json");
                $this->line("  FIREBASE_CREDENTIALS_JSON=<base64_encoded_json>");
                return Command::FAILURE;
            }

            // Initialize Firebase with proper credentials format
            $factory = new Factory();
            
            // Handle different credential formats
            if (isset($credentials['file']) && !empty($credentials['file'])) {
                $filePath = $credentials['file'];
                
                // Check if path is relative, make it absolute from base_path
                if (!str_starts_with($filePath, '/') && !preg_match('/^[A-Z]:/i', $filePath)) {
                    $filePath = base_path($filePath);
                }
                
                if (!file_exists($filePath)) {
                    $this->error("Firebase credentials file not found: {$filePath}");
                    $this->warn("Current FIREBASE_CREDENTIALS: " . env('FIREBASE_CREDENTIALS'));
                    return Command::FAILURE;
                }
                
                $factory = $factory->withServiceAccount($filePath);
            } elseif (isset($credentials['json']) && is_array($credentials['json'])) {
                $factory = $factory->withServiceAccount($credentials['json']);
            } else {
                $this->error("Invalid Firebase credentials format!");
                return Command::FAILURE;
            }
            
            $auth = $factory->createAuth();

            // Create custom token with additional claims
            $customToken = $auth->createCustomToken(
                $user->firebase_uid,
                ['premiumAccount' => true]
            );

            // Save to database
            $user->custom_token = $customToken->toString();
            $user->save();

            $this->info("✅ Custom token regenerated successfully for {$email}!");
            $this->line("Token: {$user->custom_token}");
            $this->newLine();
            $this->warn("⏰ Note: This token will expire in 1 hour.");
            $this->info("💡 Tip: Run this command again when token expires.");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to regenerate token: " . $e->getMessage());
            $this->warn("Stack trace:");
            $this->line($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}

