<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Exception\Auth\RevokedIdToken;
use Symfony\Component\HttpFoundation\Response;
use Kreait\Firebase\Contract\Auth as FireAuth;


class FirebaseAuthMiddleware
{
    protected FireAuth $firebaseAuth;

    public function __construct(FireAuth $firebaseAuth)
    {

        $this->firebaseAuth = $firebaseAuth;
    }

    public function handle(Request $request, Closure $next)
    {
        Log::debug('FirebaseAuthMiddleware - Starting token verification');

        $token = $request->bearerToken(); // Get token from Authorization header

        if (!$token) {
            Log::warning('FirebaseAuthMiddleware - No bearer token provided');
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        Log::debug('FirebaseAuthMiddleware - Token received', ['token_preview' => substr($token, 0, 50) . '...']);

        try {
            Log::debug('FirebaseAuthMiddleware - Attempting to verify token');
            $verifiedIdToken = $this->firebaseAuth->verifyIdToken($token);
            $firebaseUid = $verifiedIdToken->claims()->get('sub'); // Get the UID from the token

            // Fetch the user from the database
            $user = \App\Models\User::where('firebase_uid', $firebaseUid)->first();

            if (!$user) {
                Log::warning('FirebaseAuthMiddleware - User not found', ['uid' => $firebaseUid]);
                return response()->json(['error' => 'User not found'], Response::HTTP_UNAUTHORIZED);
            }

            // Set the user in the authentication context
            auth()->setUser($user);
            Log::info('FirebaseAuthMiddleware - User authenticated successfully', ['uid' => $firebaseUid]);

            // Optionally, attach the Firebase user claims to the request
            $request->attributes->set('firebaseUser', $verifiedIdToken->claims()->all());
            Log::info('FirebaseAuthMiddleware - Token verified successfully', ['uid' => $verifiedIdToken->claims()->get('sub')]);
            //            return \response()->json($request->attributes->get('firebaseUser'));
        } catch (RevokedIdToken $e) {
            Log::error('FirebaseAuthMiddleware - Token revoked', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Token revoked'], Response::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            Log::error('FirebaseAuthMiddleware - Token verification failed', [
                'message' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['error' => 'Invalid token', 'debug' => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
