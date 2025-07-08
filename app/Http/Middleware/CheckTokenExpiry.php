<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckTokenExpiry
{
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['message' => 'Missing or invalid token',   'error' => 'true',], 401);
        }

        $plainToken = substr($authHeader, 7);
        $hashedToken = hash('sha256', $plainToken);

        $token = DB::table('personal_access_tokens')->where('token', $hashedToken)->first();

        if (!$token) {
            return response()->json(['message' => 'Invalid token.',   'error' => 'true',], 401);
        }

        if ($token->expires_at && now()->greaterThan($token->expires_at)) {
            return response()->json(['message' => 'Token has expired.'], 401);
        }

        // Optionally attach user to request for convenience
        $userModel = app($token->tokenable_type);
        $user = $userModel->find($token->tokenable_id);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
