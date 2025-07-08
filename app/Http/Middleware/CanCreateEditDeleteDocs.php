<?php

namespace App\Http\Middleware;

// app/Http/Middleware/CanCreateEditDeleteDocs.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CanCreateEditDeleteDocs
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'super_admin') {
            return response()->json([
                'error' => 'Forbidden. Only super_admins can modify documents.',
                'code' => '23'
            ], 401);
        }

        return $next($request);
    }
}
