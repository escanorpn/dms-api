<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Unauthorized. No token provided.',   'code' => '22',], 401);
        }

        // Hash the token before lookup (just like Sanctum)
        $hashedToken = hash('sha256', $token);

        $record = DB::table('personal_access_tokens')->where('token', $hashedToken)->first();

        if (!$record) {
            return response()->json(['error' => 'Unauthorized. Invalid token.',   'code' => '22',], 401);
        }

        $user = User::find($record->tokenable_id);

        if (!$user) {
            return response()->json(['error' => 'Unauthorized. User not found.',   'code' => '22',], 401);
        }

        
        // Set user in the request
        auth()->setUser($user);

        return $next($request);
    }
}
