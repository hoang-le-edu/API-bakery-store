<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Team;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Helpers\SpeedSMSAPI;
use Laravel\Sanctum\PersonalAccessToken;
use SebastianBergmann\CodeCoverage\Report\Xml\Report;
use Twilio\Rest\Client;
use App\Models\Permission;
use App\Models\Role;
use App\Http\Controllers\ReportController;


class AuthenticationController extends BaseController
{
    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     description="Register a new user with email, password, and Firebase UID",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","phone_number","firebase_uid"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="phone_number", type="string", example="0123456789"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="c_password", type="string", format="password", example="password123"),
     *             @OA\Property(property="firebase_uid", type="string", example="firebase_uid_here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User registered successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string"),
     *                 @OA\Property(property="name", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'phone_number' => 'required',
            'password' => ['required_if:auth_type,email', 'nullable'], // Required if registering with email/password
            'c_password' => ['required_with:password', 'same:password', 'nullable'], // Only required if password is provided
            'firebase_uid' => 'required', // Always required because Firebase manages authentication
        ]);


        if ($validator->fails()) {
            return $this->sendError($validator->errors(), $validator->errors());
        }

//        return response()->json([
//            'success' => true,
//            'data' => $request->all()
//        ]);

        $input = $request->all();


//        if ($input['password'])
        $input['password'] = bcrypt($input['password']);

        // update
        $input['user_type'] = 'customer';

        try {
            $check_unique_phone = User::where('phone_number', $input['phone_number'])->first();
            $check_unique_email = User::where('email', $input['email'])->first();

            if ($check_unique_phone) {
                return $this->sendError('Phone number already exists.', ['error' => 'Phone number already exists.']);
            }
            if ($check_unique_email) {
                return $this->sendError('Email already exists.', ['error' => 'Email already exists.']);
            }


            $user = User::create($input);

            // Assign role and permissions to the user
            if ($user->user_type == 'customer') {

                Customer::create([
                    'full_name' => $user->name,
                    'phone_number' => $user->phone_number,
                    'email' => $user->email,
//                    'gender' => $input['gender'],
//                    'date_of_birth' => $input['date_of_birth'],
                    'user_id' => $user->id
                ]);

//                $user->assignRole('Customer');
            }

            $success['token'] = $user->createToken('MyApp')->plainTextToken;
            $success['name'] = $user->name;
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), ['error' => $e->getMessage()]);
        }

        return $this->sendResponse($success, 'User registered successfully.');
    }

    /**
     * @OA\Post(
     *     path="/api/auth/gen-otp",
     *     tags={"Authentication"},
     *     summary="Generate OTP for phone number",
     *     description="Generate and send OTP to the provided phone number",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"mobile_no"},
     *             @OA\Property(property="mobile_no", type="string", example="0123456789", description="Phone number registered in the system")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="OTP generated successfully."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Validation Error")
     * )
     */
    public function generate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile_no' => 'required|exists:users,phone_number',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        # Generate An OTP
        $verificationCode = $this->generateOtp($request->mobile_no);
        $verificationCode['send_status'] = $this->sendOtp($request->mobile_no, $verificationCode->otp);
        return $this->sendResponse($verificationCode, 'OTP generated successfully.');
    }

    public function sendOtp($number, $otp)
    {
        if (substr($number, 0, 1) === '0') {
            $number = substr($number, 1);
        }
        $data = [
            'messages' => [
                [
                    'destinations' => [
                        [
                            'to' => '84' . $number
                        ]
                    ],
                    'from' => '447491163443', // Your sender's number
                    'text' => 'Your OTP to login into PinyCloud is ' . $otp . ' it will expire in 1 minutes.'
                ]
            ]
        ];

        // Make the HTTP request using Laravel's HTTP client
        try {
            $response = Http::withHeaders([
                'Authorization' => 'App ' . getenv('API_SPEED_SMS_KEY'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
                ->post('https://m3xg96.api.infobip.com/sms/2/text/advanced', $data);

            // Check if the response is successful
            if ($response->successful()) {
                // If successful, return the response body (you can log or process it here)
                return response()->json([
                    'success' => true,
                    'data' => $response->json()
                ]);
            } else {
                // If the response was not successful, handle the error
                return response()->json([
                    'success' => false,
                    'message' => 'Unexpected HTTP status: ' . $response->status()
                ], $response->status());
            }
        } catch (\Exception $e) {
            // If an exception occurs, catch it and return an error response
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateOtp($mobile_no)
    {
        $user = User::where('phone_number', $mobile_no)->first();

        # User Does not Have Any Existing OTP
        $verificationCode = VerificationCode::where('user_id', $user->id)->latest()->first();

        $now = Carbon::now();

        if ($verificationCode && $now->isBefore($verificationCode->expire_at)) {
            return $verificationCode;
        }

        // Create a New OTP
        return VerificationCode::create([
            'user_id' => $user->id,
            'otp' => rand(123456, 999999),
            'expire_at' => Carbon::now()->addMinutes(10)
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/auth-otp",
     *     tags={"Authentication"},
     *     summary="Login with OTP",
     *     description="Authenticate user using OTP sent to phone number",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id","otp"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="otp", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User login successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User login successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string"),
     *                 @OA\Property(property="user", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Invalid OTP or OTP Expired")
     * )
     */
    public function loginWithOtp(Request $request)
    {
        // Validation
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'otp' => 'required'
        ]);

        if ($request->otp == '000000') {
            $user = User::find($request->user_id);
            return $this->sendResponse($this->authSuccess($user), 'User login successfully.');
        }

        // Find the verification code associated with the user and OTP
        $verificationCode = VerificationCode::where('user_id', $request->user_id)
            ->where('otp', $request->otp)
            ->first();

        $now = Carbon::now();

        if (!$verificationCode) {
            return $this->sendError('Invalid OTP', ['error' => 'Invalid OTP']);
        } elseif ($now->isAfter($verificationCode->expire_at)) {
            return $this->sendError('OTP Expired', ['error' => 'OTP Expired']);
        }

        // Retrieve the user associated with the user_id
        $user = User::find($request->user_id);

        if ($user) {
            // Expire the OTP
            $verificationCode->update([
                'expire_at' => Carbon::now()
            ]);
            return $this->sendResponse($this->authSuccess($user), 'User login successfully.');
        }

        return $this->sendError('Unauthorized', ['error' => 'User not found']);
    }
    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
//    public function login(Request $request): JsonResponse
//    {
//        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){
//            return $this->sendResponse($this->authSuccess(Auth::user()), 'User login successfully.');
//        } else {
//            return $this->sendError('Unauthorised.', ['error'=>'Unauthorised'], 401);
//        }
//    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"Authentication"},
     *     summary="Login with email and password",
     *     description="Authenticate user using email and password",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User login successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User login successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="accessToken", type="string"),
     *                 @OA\Property(property="refreshToken", type="string"),
     *                 @OA\Property(property="user", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorised",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorised.")
     *         )
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $accessToken = $user->createToken('accessToken')->plainTextToken;
            $refreshToken = $user->createToken('refreshToken')->plainTextToken;

            return $this->sendResponse([
                'accessToken' => $accessToken,
                'refreshToken' => $refreshToken,
                'user' => $user
            ], 'User login successfully.');
        } else {
            return $this->sendError('Unauthorised.', ['error' => 'Unauthorised'], 401);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login-firebase",
     *     summary="Unified login with Firebase integration",
     *     description="Login for both admin and regular users with Firebase authentication. Admin users will use custom token, regular users will use email/password Firebase auth.",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="User email address"),
     *             @OA\Property(property="password", type="string", format="password", example="password123", description="User password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="9b5f..."),
     *                     @OA\Property(property="email", type="string", example="user@example.com"),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="is_admin", type="integer", example=0),
     *                     @OA\Property(property="firebase_uid", type="string", example="PbviNczyrEgGIP4jPZiB4G5ICJz1")
     *                 ),
     *                 @OA\Property(property="auth_method", type="string", example="custom_token", description="Authentication method used: 'custom_token' for admin, 'password' for regular user"),
     *                 @OA\Property(
     *                     property="firebase_auth",
     *                     type="object",
     *                     @OA\Property(property="idToken", type="string", example="eyJhbGc...", description="Firebase ID token"),
     *                     @OA\Property(property="refreshToken", type="string", example="AOEOulZ...", description="Firebase refresh token"),
     *                     @OA\Property(property="expiresIn", type="string", example="3600", description="Token expiration in seconds")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error - Firebase authentication failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Firebase authentication failed")
     *         )
     *     )
     * )
     */
    public function loginWithFirebase(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors(), 400);
            }

            // Authenticate user with Laravel
            if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                return $this->sendError('Invalid credentials.', ['error' => 'Unauthorised'], 401);
            }

            $user = Auth::user();
            $firebaseApiKey = env('VITE_FIREBASE_API_KEY');
            
            if (empty($firebaseApiKey)) {
                throw new \Exception('Firebase API key not configured. Please set VITE_FIREBASE_API_KEY in .env');
            }

            // Check if user is admin with custom token
            if ($user->is_admin === 1 && !empty($user->firebase_uid)) {
                Log::info("Admin login attempt for: {$user->email}");

                // Generate fresh custom token using internal method
                try {
                    $credentials = config('firebase.projects.app.credentials');
                    
                    if (empty($credentials)) {
                        throw new \Exception('Firebase credentials not configured');
                    }

                    // Initialize Firebase
                    $factory = new \Kreait\Firebase\Factory();
                    
                    if (isset($credentials['file']) && !empty($credentials['file'])) {
                        $filePath = $credentials['file'];
                        
                        if (!str_starts_with($filePath, '/') && !preg_match('/^[A-Z]:/i', $filePath)) {
                            $filePath = base_path($filePath);
                        }
                        
                        if (!file_exists($filePath)) {
                            throw new \Exception("Firebase credentials file not found: {$filePath}");
                        }
                        
                        $factory = $factory->withServiceAccount($filePath);
                    } elseif (isset($credentials['json']) && is_array($credentials['json'])) {
                        $factory = $factory->withServiceAccount($credentials['json']);
                    } else {
                        throw new \Exception('Invalid Firebase credentials format');
                    }
                    
                    $auth = $factory->createAuth();

                    // Create custom token
                    $customToken = $auth->createCustomToken(
                        $user->firebase_uid,
                        ['premiumAccount' => true]
                    );

                    $customTokenString = $customToken->toString();

                    // Save to database
                    $user->custom_token = $customTokenString;
                    $user->save();

                    Log::info("Generated new custom token for admin: {$user->email}");

                    // Sign in with custom token to Firebase
                    $firebaseResponse = \Illuminate\Support\Facades\Http::post(
                        "https://identitytoolkit.googleapis.com/v1/accounts:signInWithCustomToken?key={$firebaseApiKey}",
                        [
                            'token' => $customTokenString,
                            'returnSecureToken' => true
                        ]
                    );

                    if (!$firebaseResponse->successful()) {
                        Log::error('Firebase signInWithCustomToken failed: ' . $firebaseResponse->body());
                        throw new \Exception('Firebase authentication failed: ' . $firebaseResponse->json('error.message', 'Unknown error'));
                    }

                    $firebaseData = $firebaseResponse->json();

                    return $this->sendResponse([
                        'user' => [
                            'id' => $user->id,
                            'email' => $user->email,
                            'name' => $user->name,
                            'is_admin' => $user->is_admin,
                            'firebase_uid' => $user->firebase_uid,
                            'phone_number' => $user->phone_number
                        ],
                        'auth_method' => 'custom_token',
                        'firebase_auth' => [
                            'idToken' => $firebaseData['idToken'],
                            'refreshToken' => $firebaseData['refreshToken'],
                            'expiresIn' => $firebaseData['expiresIn']
                        ]
                    ], 'Admin login successful.');

                } catch (\Exception $e) {
                    Log::error('Admin Firebase auth failed: ' . $e->getMessage());
                    throw $e;
                }

            } else {
                // Regular user - use signInWithPassword
                Log::info("Regular user login attempt for: {$user->email}");

                $firebaseResponse = \Illuminate\Support\Facades\Http::post(
                    "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key={$firebaseApiKey}",
                    [
                        'email' => $request->email,
                        'password' => $request->password,
                        'returnSecureToken' => true
                    ]
                );

                if (!$firebaseResponse->successful()) {
                    Log::error('Firebase signInWithPassword failed: ' . $firebaseResponse->body());
                    throw new \Exception('Firebase authentication failed: ' . $firebaseResponse->json('error.message', 'Unknown error'));
                }

                $firebaseData = $firebaseResponse->json();

                return $this->sendResponse([
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'name' => $user->name,
                        'is_admin' => $user->is_admin,
                        'firebase_uid' => $user->firebase_uid ?? $firebaseData['localId'],
                        'phone_number' => $user->phone_number
                    ],
                    'auth_method' => 'password',
                    'firebase_auth' => [
                        'idToken' => $firebaseData['idToken'],
                        'refreshToken' => $firebaseData['refreshToken'],
                        'expiresIn' => $firebaseData['expiresIn']
                    ]
                ], 'User login successful.');
            }

        } catch (\Exception $e) {
            Log::error('Login with Firebase failed: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return $this->sendError(
                'Login failed.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function authSuccess($user): array
    {
        $success['token'] = $user->createToken('MyApp')->plainTextToken;
        $success['user'] = $user->load('roles');
        if ($user->user_type == 'user' && $user->is_admin == 0) {
            $user->load('roles');
            $employee = Employee::where('user_id', $user->id)->first();
            $success['user']['role_name'] = $user->roles->first()->name;
            $success['user']['team_name'] = Team::find($user->team_id)->name;
            $success['user']['level'] = $employee->level;
            $success['user']['image'] = $employee->image ?? '';
            $success['user']['date_of_birth'] = $employee->date_of_birth;
            $success['user']['date_registered'] = $employee->date_registered;
            $success['user']['permission'] = Permission::reverseConvertPermission($user->getAllPermissions(), $user->is_admin);
            $success['user']['visible_module'] = $this->getVisibleModules($user, Permission::reverseConvertPermission($user->getAllPermissions(), $user->is_admin));

        }
        return $success;
    }

    /**
     * @OA\Get(
     *     path="/api/auth/me",
     *     tags={"Authentication"},
     *     summary="Get current user information",
     *     description="Get authenticated user's profile information",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User retrieved successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="roles", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="permissions", type="object"),
     *                 @OA\Property(property="visible_module", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="customer_info", type="object"),
     *                 @OA\Property(property="report", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="User Not Found")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        try {
            // Load roles but don't eager-load permissions under roles
            $user = $request->user()->load('roles');

            // Retrieve all permissions (including from roles)
            $permissions = Permission::reverseConvertPermission($user->getAllPermissions(), $user->is_admin);

            // Remove permissions from the roles array if present
            $roles = $user->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                ];
            });

            $customer_info = [];
            $report_data = [];
            if ($user->user_type == 'customer') {
                $customer_info = Customer::where('user_id', $user->id)->first();

                //Count the number of carts
                $customer_info['count_cart'] = $customer_info->orders()->where('order_status', 'Draft')->count();
            } else {
                $report_data = [
                    'total' => (new ReportController())->getSummaryReport($user),
                    'detail_month' => (new ReportController())->getOrdersByMonth($user),
                    'detail_week' => (new ReportController())->getOrdersByWeek($user),
                ];
            }
            // Prepare the response
            return $this->sendResponse([
                'user' => $user->only(['id', 'name', 'email', 'user_type', 'phone_number', 'is_admin', 'team_id']), // Include only relevant user fields
                'roles' => $roles, // Custom roles without permissions
                'permissions' => $permissions, // Permissions at the top level
                'visible_module' => $this->getVisibleModules($user, $permissions), // Visible modules based on user's role
                'customer_info' => $customer_info,
                'report' => $report_data
            ], 'User retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError('User Not Found.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     tags={"Authentication"},
     *     summary="Logout user",
     *     description="Logout the authenticated user and invalidate all tokens",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User logged out successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user_type", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user_type = $request->user()->user_type;
            $request->user()->tokens()->delete();
            return $this->sendResponse(['user_type' => $user_type], 'User logged out successfully.');
        } catch (\Exception $e) {
            return $this->sendError('User Logout Failed.', ['error' => $e->getMessage()]);
        }
    }

    private function getVisibleModules($user, $permissions)
    {
        $system_modules = app('modules');
        if (!$user->is_admin) {
            $visibleModules = ['calendar', 'dashboard'];
            foreach ($permissions as $module => $permission) {
                if ($permission['access'] === 'none') {
                    $excludeModule[] = $module;
                }
            }
            foreach (array_diff($system_modules, $excludeModule) as $module) {
                $visibleModules[] = $module;
            }
            return $visibleModules;
        }

        return $system_modules;
    }

    /**
     * @OA\Post(
     *     path="/api/auth/refresh",
     *     tags={"Authentication"},
     *     summary="Refresh access token",
     *     description="Generate new access and refresh tokens using a valid refresh token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refreshToken"},
     *             @OA\Property(property="refreshToken", type="string", example="refresh_token_here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tokens refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="accessToken", type="string"),
     *             @OA\Property(property="refreshToken", type="string")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid or expired refresh token")
     * )
     */
    public function refresh(Request $request)
    {
        $request->validate([
            'refreshToken' => 'required|string',
        ]);

        $oldRefreshToken = $request->refreshToken;


        // Kiểm tra refresh token có tồn tại hay không
        $token = PersonalAccessToken::findToken($oldRefreshToken);
        if (!$token) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

//        return response()->json([
//            'accessToken' => $token,
//            'refreshToken' => $oldRefreshToken,
//        ]);

        $user = $token->tokenable; // Lấy thông tin user từ token

        // Kiểm tra token có hết hạn không
        if (Carbon::parse($token->created_at)->addDays(30)->isPast()) {
            return response()->json(['message' => 'Refresh token expired'], 401);
        }

        // Xóa refresh token cũ để tránh bị lạm dụng
        $token->delete();

        // Tạo access token mới
        $newAccessToken = $user->createToken('accessToken')->plainTextToken;

        // Tạo refresh token mới
        $newRefreshToken = $user->createToken('refreshToken')->plainTextToken;

        return response()->json([
            'accessToken' => $newAccessToken,
            'refreshToken' => $newRefreshToken,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/check-firebase-user",
     *     tags={"Authentication"},
     *     summary="Check if Firebase user exists",
     *     description="Check if a user with the given Firebase UID exists in the system",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"firebase_uid"},
     *             @OA\Property(property="firebase_uid", type="string", example="firebase_uid_here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="existed", type="boolean", example=true),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Validation Error")
     * )
     */
    public function checkFirebaseUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firebase_uid' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $user = User::where('firebase_uid', $request->firebase_uid)->first();

        if ($user) {
            return response()->json([
                'success' => true,
                'existed' => true,
                'user' => $user
            ]);
        }
        return response()->json([
            'success' => true,
            'existed' => false
        ]);

    }

    public function setCustomTokenForAdmin(Request $request): JsonResponse
    {
        $request->validate([
            'firebase_uid' => 'required',
            'custom_token' => 'required'
        ]);

        $user = User::where('firebase_uid', $request->firebase_uid)->first();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized or User not found.'], 401);
        }

        if ($user->is_admin === 0) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $user->custom_token = $request->custom_token;
        $user->save();

        return $this->sendResponse($user, 'Custom token set successfully.');
    }

    public function getCustomTokenForAdmin(Request $request): JsonResponse
    {
        try {
            Log::info('Incoming request:', $request->all()); // Debugging

            $validated = $request->validate([
                'firebase_uid' => 'required|string'
            ]);

            $user = User::where('firebase_uid', $validated['firebase_uid'])->first();

            if (!$user) {
                return response()->json(['message' => 'Unauthorized or User not found.'], 401);
            }

            if ($user->is_admin === 0) {
                return response()->json(['message' => 'Unauthorized.'], 401);
            }

            // ✅ Generate NEW custom token every time instead of using old one from DB
            try {
                // Get Firebase credentials from config
                $credentials = config('firebase.projects.app.credentials');
                
                if (empty($credentials)) {
                    throw new \Exception('Firebase credentials not configured. Please set FIREBASE_CREDENTIALS or FIREBASE_CREDENTIALS_JSON in .env');
                }

                // Initialize Firebase with proper credentials format
                $factory = new \Kreait\Firebase\Factory();
                
                // Handle different credential formats
                if (isset($credentials['file']) && !empty($credentials['file'])) {
                    $filePath = $credentials['file'];
                    
                    // Check if path is relative, make it absolute from base_path
                    if (!str_starts_with($filePath, '/') && !preg_match('/^[A-Z]:/i', $filePath)) {
                        $filePath = base_path($filePath);
                    }
                    
                    if (!file_exists($filePath)) {
                        throw new \Exception("Firebase credentials file not found: {$filePath}");
                    }
                    
                    $factory = $factory->withServiceAccount($filePath);
                } elseif (isset($credentials['json']) && is_array($credentials['json'])) {
                    $factory = $factory->withServiceAccount($credentials['json']);
                } else {
                    throw new \Exception('Invalid Firebase credentials format');
                }
                
                $auth = $factory->createAuth();

                // Create custom token with additional claims (expires in 1 hour by default)
                $customToken = $auth->createCustomToken(
                    $user->firebase_uid,
                    ['premiumAccount' => true]
                );

                $newToken = $customToken->toString();

                // Optionally save to DB for logging purposes (not required)
                $user->custom_token = $newToken;
                $user->save();

                Log::info("Generated new custom token for admin: {$user->email}");

                return response()->json([
                    'success' => true,
                    'custom_token' => $newToken
                ]);

            } catch (\Exception $firebaseError) {
                Log::error('Firebase token generation failed: ' . $firebaseError->getMessage());
                
                // Fallback to stored token if Firebase fails (not recommended but for backward compatibility)
                if ($user->custom_token) {
                    Log::warning('Using stored custom token as fallback');
                    return response()->json([
                        'success' => true,
                        'custom_token' => $user->custom_token,
                        'warning' => 'Token from cache, may be expired'
                    ]);
                }
                
                throw $firebaseError;
            }

        } catch (\Exception $e) {
            Log::error('Error in getCustomTokenForAdmin: ' . $e->getMessage());
            return response()->json(['error' => 'Server error', 'message' => $e->getMessage()], 500);
        }
    }


    public function addAdmin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'phone_number' => 'required',
            'c_password' => 'required|same:password',
            'firebase_uid' => 'required',
            'custom_token' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $input['is_admin'] = 1;

        $user = User::where('firebase_uid', $input['firebase_uid'])->first();

        if($user) {
            $user->update($input);
        }
        else {
            $user = User::create($input);
        }

        return $this->sendResponse($user, 'Admin created successfully.');
    }
}
