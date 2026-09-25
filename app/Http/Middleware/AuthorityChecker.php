<?php

namespace App\Http\Middleware;

use App\Models\AuthorityModel;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthorityChecker
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        /*
         * Guests do not have authority records.
         * The route's auth middleware protects private pages.
         */
        if (Auth::guest() || $request->is('mindar', 'mindar/*', 'zoho', 'zoho/*')) {
            return $next($request);
        }

        /*
         * Let authenticated users sign out.
         */
        if ($request->is('sign-out')) {
            return $next($request);
        }

        $permissions = AuthorityModel::join(
            'table_urls',
            'table_urls.id',
            '=',
            'authority.linkName_id'
        )
            ->where('authority.user_id', Auth::id())
            ->get();

        foreach ($permissions as $permission) {
            $exactMatch = $request->is(
                $permission->linkName
            );

            $childMatch = $request->is(
                $permission->linkName.'/*'
            );

            if ($exactMatch || $childMatch) {
                return $next($request);
            }
        }

        // Never redirect an unauthorized request to another protected page.
        // Doing so can redirect /dashboard back to itself forever.
        abort(403, 'You are not authorized to open this page.');
    }
}
