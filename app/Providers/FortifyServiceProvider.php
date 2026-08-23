<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;                                    // เพิ่มใหม่
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;                    // เพิ่มใหม่
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;          // เพิ่มใหม่
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /* ============================================================
         |  กำหนดวิธีตรวจสอบการเข้าสู่ระบบเอง  (เพิ่มใหม่)
         |  เพิ่มเงื่อนไข: บัญชีต้องไม่ถูกระงับการใช้งาน
         ============================================================ */
        Fortify::authenticateUsing(function (Request $request) {

            // ค้นหาผู้ใช้จากอีเมลที่กรอกเข้ามา
            $user = User::where('email', $request->email)->first();

            // ไม่พบผู้ใช้ หรือ รหัสผ่านไม่ถูกต้อง
            // คืนค่า null เพื่อให้ Fortify แสดงข้อความผิดพลาดมาตรฐาน
            if (! $user || ! Hash::check($request->password, $user->password)) {
                return null;
            }

            // รหัสผ่านถูกต้อง แต่บัญชีถูกระงับโดยผู้ดูแลระบบ
            if (! $user->is_active) {
                throw ValidationException::withMessages([
                    Fortify::username() => 'บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ',
                ]);
            }

            // ผ่านทุกเงื่อนไข อนุญาตให้เข้าสู่ระบบ
            return $user;
        });

        /* ---------- ส่วนเดิมของ Jetstream ---------- */
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}