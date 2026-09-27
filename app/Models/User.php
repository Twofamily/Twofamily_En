<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\Permission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /* ============================================================
     |  ค่าคงที่ของสิทธิ์
     |  ใช้แทนการพิมพ์ string ตรง ๆ กันพิมพ์ผิด
     |  เรียกใช้:  User::ROLE_ADMIN
     ============================================================ */
    public const ROLE_ADMIN  = 'admin';
    public const ROLE_STAFF  = 'staff';
    public const ROLE_VIEWER = 'viewer';

    /**
     * รายการสิทธิ์ทั้งหมด + ชื่อภาษาไทย
     * ใช้ทำ dropdown ในหน้าเพิ่ม/แก้ไขผู้ใช้
     */
    public static function roleList(): array
    {
        return [
            self::ROLE_ADMIN  => 'ผู้ดูแลระบบ',
            self::ROLE_STAFF  => 'พนักงานเอกสาร',
            self::ROLE_VIEWER => 'ผู้ดูข้อมูล (ดูอย่างเดียว)',
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'permissions',      // สิทธิ์รายหน้า (JSON)
        'is_active',
        'signature_path',   // path รูปลายเซ็น สัมพัทธ์จาก public/
        'position',         // ตำแหน่งที่พิมพ์ใต้ชื่อในเอกสาร
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'permissions'       => 'array',
        ];
    }

    /* ============================================================
     |  เมธอดช่วยตรวจสอบสิทธิ์
     |  ใช้ใน Blade เช่น  @if(auth()->user()->isAdmin())
     ============================================================ */

    /** เป็นผู้ดูแลระบบหรือไม่ */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** เป็นพนักงานเอกสารหรือไม่ */
    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    /** เป็นผู้บริหาร (ดูอย่างเดียว) หรือไม่ */
    public function isViewer(): bool
    {
        return $this->role === self::ROLE_VIEWER;
    }

    /**
     * มีสิทธิ์ตรงกับที่ระบุหรือไม่ (ระบุได้หลายตัว)
     * ตัวอย่าง: $user->hasRole('admin', 'staff')
     */
    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * มีสิทธิ์เพิ่ม/แก้ไข/ลบข้อมูลหรือไม่
     * ใช้ครอบปุ่มในหน้า Blade:  @if(auth()->user()->canEdit())
     *
     * ถ้าไม่ระบุหน้า จะดูจากหน้าปัจจุบันให้อัตโนมัติ
     * ปุ่มที่ครอบ canEdit() ไว้แล้วจึงเคารพสิทธิ์รายหน้าทันทีโดยไม่ต้องแก้ view
     * ระบุเองได้: canEdit('quotations')
     */
    public function canEdit(?string $module = null): bool
    {
        if (! $this->hasRole(self::ROLE_ADMIN, self::ROLE_STAFF)) {
            return false;
        }

        $module ??= Permission::moduleFromRoute(request()->route()?->getName());

        return $module ? $this->canEditModule($module) : true;
    }

    /** ชื่อสิทธิ์ภาษาไทย สำหรับแสดงในตาราง */
    public function getRoleNameAttribute(): string
    {
        return self::roleList()[$this->role] ?? 'ไม่ระบุ';
    }

    /* ============================================================
     |  สิทธิ์รายหน้า
     ============================================================ */

    /**
     * ระดับสิทธิ์จริงของหน้านั้น (0 = ไม่มี, 1 = ดู, 2 = แก้ไข)
     * = ค่าที่ต่ำกว่าระหว่างเพดานของ role กับค่าที่ตั้งรายคน
     */
    public function permissionLevel(string $module): int
    {
        if ($this->isAdmin()) {
            return Permission::LEVELS[Permission::EDIT];
        }

        $cap    = Permission::toInt(Permission::defaultFor($this->role));
        $custom = $this->permissions[$module] ?? null;

        // ยังไม่เคยตั้งหน้านี้ → ใช้ตาม role
        if ($custom === null) {
            return $cap;
        }

        return min($cap, Permission::toInt($custom));
    }

    /** เข้าดูหน้านี้ได้หรือไม่  ใช้ซ่อนเมนู: canViewModule('quotations') */
    public function canViewModule(string $module): bool
    {
        return $this->permissionLevel($module) >= Permission::LEVELS[Permission::VIEW];
    }

    /** แก้ไขข้อมูลในหน้านี้ได้หรือไม่ */
    public function canEditModule(string $module): bool
    {
        return $this->permissionLevel($module) >= Permission::LEVELS[Permission::EDIT];
    }

    /* ============================================================
     |  ลายเซ็นอิเล็กทรอนิกส์
     ============================================================ */

    /**
     * มีไฟล์ลายเซ็นที่ใช้งานได้จริงหรือไม่
     * เช็คถึงระดับไฟล์ เพราะ path ในฐานข้อมูลอาจชี้ไปหาไฟล์ที่ถูกลบไปแล้ว
     */
    public function hasSignature(): bool
    {
        return $this->signature_path
            && file_exists(public_path($this->signature_path));
    }

    /**
     * path เต็มของไฟล์ลายเซ็น สำหรับ dompdf
     * คืน null ถ้าไม่มีไฟล์ เพื่อให้ Blade ข้ามการแสดงรูปไปเลย
     *
     * หมายเหตุ: dompdf อ่าน URL ไม่ได้ ต้องใช้ path ในเครื่องเท่านั้น
     * จึงใช้ public_path() ไม่ใช่ asset()
     */
    public function getSignatureFullPathAttribute(): ?string
    {
        return $this->hasSignature() ? public_path($this->signature_path) : null;
    }
}