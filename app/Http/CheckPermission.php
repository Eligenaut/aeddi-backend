<?php

namespace App\Http;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\Permissions;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        if (!Permissions::userHas($user, $permission)) {
            return Permissions::denied($permission);
        }

        return $next($request);
    }
}
