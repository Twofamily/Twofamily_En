<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * ค่าตั้งค่าระบบ แบบ key-value
 *
 * รายการ key ทั้งหมด ค่าเริ่มต้น และกฎ validation อยู่ที่ config/settings.php
 *
 * วิธีใช้:
 *   Setting::get('doc.vat_rate')        // 7.0  (แปลงชนิดให้ตาม type)
 *   Setting::get('doc.prefix.invoice')  // 'INV'
 *   Setting::setMany(['doc.vat_rate' => 7]);
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    private const CACHE_KEY = 'settings.values';

    protected static function booted(): void
    {
        // ล้าง cache ทุกครั้งที่บันทึกหรือลบ
        // ครอบคลุมโค้ดเดิมที่เรียก updateOrCreate ตรง ๆ ด้วย
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    /* ==================== ทะเบียน ==================== */

    public static function definitions(): array
    {
        return config('settings.definitions', []);
    }

    public static function definition(string $key): ?array
    {
        return static::definitions()[$key] ?? null;
    }

    /** key ทั้งหมดในแท็บที่ระบุ */
    public static function definitionsFor(string $group): array
    {
        return array_filter(
            static::definitions(),
            fn (array $def) => ($def['group'] ?? null) === $group
        );
    }

    /**
     * กฎ validation ของแท็บที่ระบุ
     * key แบบมีจุด เช่น doc.vat_rate จะตรวจกับ input doc[vat_rate] ได้พอดี
     */
    public static function rulesFor(string $group): array
    {
        return array_map(
            fn (array $def) => $def['rules'] ?? ['nullable'],
            static::definitionsFor($group)
        );
    }

    public static function groups(): array
    {
        return config('settings.groups', []);
    }

    public static function documentTypes(): array
    {
        return config('settings.document_types', []);
    }

    /* ==================== อ่านค่า ==================== */

    /** ค่าดิบทั้งหมดจากฐานข้อมูล (cache ไว้ ไม่ต้อง query ทุกครั้งที่เรียก) */
    public static function stored(): array
    {
        try {
            return Cache::rememberForever(
                self::CACHE_KEY,
                fn () => static::query()->pluck('value', 'key')->all()
            );
        } catch (QueryException) {
            // ตารางยังไม่ถูกสร้าง (เช่น ระหว่าง migrate:fresh)
            // ใช้ค่า default ไปก่อน และไม่ cache ผลลัพธ์ว่างนี้ไว้
            return [];
        }
    }

    /**
     * อ่านค่าหนึ่ง key พร้อมแปลงชนิดตาม type ในทะเบียน
     * ถ้ายังไม่มีในฐานข้อมูล จะคืนค่า default จากทะเบียน
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $definition = static::definition($key);
        $type       = $definition['type'] ?? 'string';
        $fallback   = $default ?? ($definition['default'] ?? null);

        $stored = static::stored();

        if (! array_key_exists($key, $stored) || $stored[$key] === null) {
            return $fallback;
        }

        $value = $stored[$key];

        // ช่องตัวเลข/สวิตช์ที่ถูกเว้นว่าง ถือว่ายังไม่ได้ตั้งค่า
        // (ส่วนช่องข้อความที่เว้นว่าง ถือว่าตั้งใจลบข้อความออก)
        if ($value === '' && in_array($type, ['int', 'decimal', 'bool'], true)) {
            return $fallback;
        }

        return static::cast($value, $type);
    }

    /**
     * ค่าทั้งหมดที่ผ่านการแปลงชนิดและเติม default แล้ว
     * key เก่าที่ไม่ได้อยู่ในทะเบียนยังคงอยู่ เพื่อไม่ให้หน้าตั้งค่าเดิมพัง
     */
    public static function merged(): array
    {
        $values = static::stored();

        foreach (array_keys(static::definitions()) as $key) {
            $values[$key] = static::get($key);
        }

        return $values;
    }

    /* ==================== เขียนค่า ==================== */

    public static function set(string $key, mixed $value): void
    {
        static::setMany([$key => $value]);
    }

    public static function setMany(array $values): void
    {
        DB::transaction(function () use ($values) {
            foreach ($values as $key => $value) {
                static::query()->updateOrCreate(
                    ['key'   => $key],
                    ['value' => static::normalize($value)]
                );
            }
        });

        // ล้างอีกรอบหลัง commit เผื่อมีคนอ่านค่าเก่าเข้า cache ระหว่าง transaction
        static::flushCache();
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /* ==================== โลโก้ ==================== */

    /** path ในเครื่อง สำหรับ dompdf (dompdf ต้องใช้ path ไม่ใช่ URL) */
    public static function logoPath(): ?string
    {
        $path = static::get('company.logo');

        if (! $path) {
            return null;
        }

        $full = public_path('storage/' . ltrim($path, '/'));

        return is_file($full) ? $full : null;
    }

    /** URL สำหรับแสดงบนหน้าเว็บ */
    public static function logoUrl(): ?string
    {
        return static::logoPath()
            ? asset('storage/' . ltrim(static::get('company.logo'), '/'))
            : null;
    }

    /* ==================== Helpers ==================== */

    private static function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'int'     => (int) $value,
            'decimal' => (float) $value,
            'bool'    => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default   => (string) $value,
        };
    }

    /** แปลงค่าก่อนเก็บลงคอลัมน์ text */
    private static function normalize(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            is_bool($value) => $value ? '1' : '0',
            default         => (string) $value,
        };
    }
}