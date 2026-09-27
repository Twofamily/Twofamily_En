<?php

namespace App\Http\Middleware;

use App\Support\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ตรวจสิทธิ์รายหน้าของผู้ใช้
 * ต้องวางต่อจาก middleware role:... ในกลุ่ม route (ซึ่งเช็กล็อกอินและบัญชีระงับไปแล้ว)
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // ยังไม่ล็อกอิน หรือเป็นผู้ดูแลระบบ → ไม่ต้องเช็ก
        if (! $user || $user->isAdmin()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $module    = Permission::moduleFromRoute($routeName);

        // route ที่ไม่ได้อยู่ในรายการหน้า (เช่น dashboard) ปล่อยผ่าน
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