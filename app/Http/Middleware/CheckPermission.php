<?php

namespace App\Http\Middleware;

use App\Support\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isAdmin()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $module    = Permission::moduleFromRoute($routeName);

        if (! $module) {
            return $next($request);
        }

        $needEdit = Permission::routeNeedsEdit($routeName, $request->method());

        $allowed = $needEdit
            ? $user->canEditModule($module)
            : $user->canViewModule($module);

        if (! $allowed) {
            abort(403, $needEdit
                ? 'คุณไม่มีสิทธิ์แก้ไขข้อมูลในหน้านี้'
                : 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        return $next($request);
    }
}