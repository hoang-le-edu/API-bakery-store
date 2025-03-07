<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
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
        $token = $request->bearerToken(); // Lấy token từ header Authorization

        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $verifiedIdToken = $this->firebaseAuth->verifyIdToken($token);
            $request->attributes->set('firebaseUser', $verifiedIdToken->claims()->all());
//            return \response()->json($request->attributes->get('firebaseUser'));
            $firebaseUid = $verifiedIdToken->claims()->get('sub'); // Get Firebase UID

            // Find or create a user in Laravel's users table
            $user = User::where('firebase_uid', $firebaseUid)->first();
            if($user){
                auth()->login($user);
            }

        } catch (RevokedIdToken $e) {
            return response()->json(['error' => 'Token revoked'], Response::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            \Log::error('Firebase token verification failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
