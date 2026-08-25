<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // ใช้ web guard สำหรับระบบล็อกอินผ่านหน้าเว็บ
        $guard = Auth::guard('web');

        // ยังไม่ได้ล็อกอิน
        if (!$guard->check()) {
            return redirect()->route('login');
        }

        $user = $guard->user();

        // บัญชีถูกระงับ
        if (!$user->is_active) {
            $guard->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ',
                ]);
        }

        // สิทธิ์ไม่ตรงกับ Route
        if (!in_array($user->role, $roles, true)) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        return $next($request);
    }
}