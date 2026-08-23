<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
     |  ค่าคงที่ของสิทธิ์  (เพิ่มใหม่)
     |  ใช้แทนการพิมพ์ string ตรง ๆ กันพิมพ์ผิด
     |  เรียกใช้:  User::ROLE_ADMIN
     ============================================================ */
    public const ROLE_ADMIN  = 'admin';
    public const ROLE_STAFF  = 'staff';
    public const ROLE_VIEWER = 'viewer';

    /**
     * รายการสิทธิ์ทั้งหมด + ชื่อภาษาไทย (เพิ่มใหม่)
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
        'role',        // เพิ่มใหม่
        'is_active',   // เพิ่มใหม่
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
            'is_active'         => 'boolean',   // เพิ่มใหม่
        ];
    }

    /* ============================================================
     |  เมธอดช่วยตรวจสอบสิทธิ์  (เพิ่มใหม่)
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
     */
    public function canEdit(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN, self::ROLE_STAFF);
    }

    /** ชื่อสิทธิ์ภาษาไทย สำหรับแสดงในตาราง */
    public function getRoleNameAttribute(): string
    {
        return self::roleList()[$this->role] ?? 'ไม่ระบุ';
    }
}