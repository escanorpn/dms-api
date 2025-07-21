<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // Store OTPs temporarily (for demo; use Redis or DB in prod)
    private static array $otpStore = [];


    private function createAccessToken(User $user): string
    {
        $plainTextToken = Str::random(40);
        $hashedToken = hash('sha256', $plainTextToken);

        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'auth_token',
            'token' => $hashedToken,
            'abilities' => json_encode(['*']),
            'created_at' => now(),
            'updated_at' => now(),
            'expires_at' => now()->addDays(7)
        ]);

        return $plainTextToken;
    }

    /**
     * Super admin login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if ($user->role !== 'super_admin') {
            return response()->json(['message' => 'Access denied for this login route'], 403);
        }

        $token = $this->createAccessToken($user);

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * DMS Admin login flow
     */
    public function dmsAdminLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Email not found'], 404);
        }

         if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (!$user || $user->role !== 'dms_admin') {
            return response()->json(['message' => 'Invalid user or role'], 401);
        }

       

        $token = $this->createAccessToken($user);

        return response()->json([
            'token' => $token,
            'password_updated' => $user->password_updated,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out']);
    }

   public function createUser(Request $request)
{
    $token = $request->user()->currentAccessToken();

  

    if ($request->user()->role !== 'super_admin') {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    // $request->validate([
    //     'email' => 'required|email|unique:users,email',
    //     'name' => 'required|string',
    // ], [
    //     'email.unique' => 'A user with this email already exists.',
    // ]);

    $validator = Validator::make($request->all(), [
    'email' => 'required|email|unique:users,email',
    'name' => 'required|string',
]);

if ($validator->fails()) {
    return response()->json([
        'error' => 'true',
        'message' => 'Validation failed',
        'errors' => $validator->errors()
    ], 422);
}

    $randomPassword = Str::random(8);

    $user = User::create([
        'email' => $request->email,
        'name' => $request->name,
        'password' => Hash::make($randomPassword),
        'role' => 'dms_admin',
        'password_updated' => false
    ]);

    $otp = rand(100000, 999999);
    self::$otpStore[$request->email] = $otp;

 try {
    Mail::raw("Your temporary password is: $randomPassword\nOTP: $otp", function ($message) use ($request) {
        $message->to($request->email)->subject('Your DMS Access Details');
    });
} catch (\Exception $e) {
    return response()->json([
        'error' => true,
        'message' => 'User created, but failed to send email.',
        'email_error' => $e->getMessage()
    ], 500);
}


    return response()->json([
        'message' => 'User created and email sent',
        'email' => $user->email,
        'temporary_password' => $randomPassword,
        'otp' => $otp,
    ]);
}

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric',
        ]);

        if (self::$otpStore[$request->email] ?? null == $request->otp) {
            return response()->json(['verified' => true]);
        }

        return response()->json(['verified' => false], 400);
    }

 public function updatePassword(Request $request)
{
    // Check if email is provided and is valid
    if (!$request->has('email') || !filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
        return response()->json(['error' => true, 'message' => 'A valid email is required'], 422);
    }

    // Check if password is provided
    if (!$request->has('password')) {
        return response()->json(['error' => true, 'message' => 'Password is required'], 422);
    }

    // Check if password confirmation is provided and matches
    if ($request->password !== $request->password_confirmation) {
        return response()->json(['error' => true, 'message' => 'Password confirmation does not match'], 422);
    }

    // Check password length
    if (strlen($request->password) < 6) {
        return response()->json(['error' => true, 'message' => 'Password must be at least 6 characters'], 422);
    }

    // Check if user with the given email exists
    $user = User::where('email', $request->email)->first();
    if (!$user) {
        return response()->json(['error' => true, 'message' => 'User not found'], 404);
    }

    // Update password
    $user->update([
        'password' => Hash::make($request->password),
        'password_updated' => true
    ]);

    return response()->json(['success' => true, 'message' => 'Password updated successfully']);
}

public function getAllUsersWithPasswords()
{
    // WARNING: This should never be exposed in production
    $users = User::select('id', 'name', 'email', 'role')->get();

    $usersWithPasswords = $users->map(function ($user) {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role,
            'temporary_password' => self::$passwordStore[$user->email] ?? 'Not stored',
        ];
    });

    return response()->json($usersWithPasswords);
}


}
