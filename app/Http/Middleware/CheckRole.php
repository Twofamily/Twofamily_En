<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * ตรวจสอบสิทธิ์ผู้ใช้ก่อนเข้าถึง route
     * วิธีใช้: ->middleware('role:admin')  หรือ  ->middleware('role:admin,staff')
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // ยังไม่ได้ล็อกอิน → ส่งไปหน้า login
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // บัญชีถูกระงับ → เตะออกจากระบบทันที
        if (!$user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ']);
        }

        // สิทธิ์ไม่ตรงกับที่ route กำหนดไว้
        if (!in_array($user->role, $roles)) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        return $next($request);
    }
}