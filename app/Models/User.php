<?php

namespace App\Models;

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

    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    public const ROLE_ADMIN  = 'admin';
    public const ROLE_STAFF  = 'staff';
    public const ROLE_VIEWER = 'viewer';

    public static function roleList(): array
    {
        return [
            self::ROLE_ADMIN  => 'ผู้ดูแลระบบ',
            self::ROLE_STAFF  => 'พนักงานเอกสาร',
            self::ROLE_VIEWER => 'ผู้ดูข้อมูล (ดูอย่างเดียว)',
        ];
    }

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'permissions',
        'is_active',
        'signature_path',
        'position',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'permissions'       => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->isDirty(['first_name', 'last_name']) && filled($user->first_name)) {
                $user->name = trim($user->first_name . ' ' . $user->last_name);
            } elseif ($user->isDirty('name')) {
                [$user->first_name, $user->last_name] = self::splitName($user->name);
            }
        });
    }

    public static function splitName(?string $name): array
    {
        $parts = preg_split('/\s+/u', trim((string) $name), 2);

        return [$parts[0] ?? '', $parts[1] ?? null];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    public function isViewer(): bool
    {
        return $this->role === self::ROLE_VIEWER;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function canEdit(?string $module = null): bool
    {
        if (! $this->hasRole(self::ROLE_ADMIN, self::ROLE_STAFF)) {
            return false;
        }

        $module ??= Permission::moduleFromRoute(request()->route()?->getName());

        return $module ? $this->canEditModule($module) : true;
    }

    public function getRoleNameAttribute(): string
    {
        return self::roleList()[$this->role] ?? 'ไม่ระบุ';
    }

    public function permissionLevel(string $module): int
    {
        if ($this->isAdmin()) {
            return Permission::LEVELS[Permission::EDIT];
        }

        $cap    = Permission::toInt(Permission::defaultFor($this->role));
        $custom = $this->permissions[$module] ?? null;

        if ($custom === null) {
            return $cap;
        }

        return min($cap, Permission::toInt($custom));
    }

    public function canViewModule(string $module): bool
    {
        return $this->permissionLevel($module) >= Permission::LEVELS[Permission::VIEW];
    }

    public function canEditModule(string $module): bool
    {
        return $this->permissionLevel($module) >= Permission::LEVELS[Permission::EDIT];
    }

    public function hasSignature(): bool
    {
        return $this->signature_path
            && file_exists(public_path($this->signature_path));
    }

    public function signatureForPdf(): ?string
    {
        if (! $this->hasSignature()) {
            return null;
        }

        $isJpeg = (bool) preg_match('/\.jpe?g$/i', $this->signature_path);

        return ($isJpeg || extension_loaded('gd')) ? public_path($this->signature_path) : null;
    }

    public function getSignatureFullPathAttribute(): ?string
    {
        return $this->hasSignature() ? public_path($this->signature_path) : null;
    }
}