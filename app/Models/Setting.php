<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    private const CACHE_KEY = 'settings.values';

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    public static function definitions(): array
    {
        return config('settings.definitions', []);
    }

    public static function definition(string $key): ?array
    {
        return static::definitions()[$key] ?? null;
    }

    public static function definitionsFor(string $group): array
    {
        return array_filter(
            static::definitions(),
            fn (array $def) => ($def['group'] ?? null) === $group
        );
    }

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

    public static function stored(): array
    {
        try {
            return Cache::rememberForever(
                self::CACHE_KEY,
                fn () => static::query()->pluck('value', 'key')->all()
            );
        } catch (QueryException) {
            return [];
        }
    }

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

        if ($value === '' && in_array($type, ['int', 'decimal', 'bool'], true)) {
            return $fallback;
        }

        return static::cast($value, $type);
    }

    public static function merged(): array
    {
        $values = static::stored();

        foreach (array_keys(static::definitions()) as $key) {
            $values[$key] = static::get($key);
        }

        return $values;
    }

    public static function set(string $key, mixed $value): void
    {
        static::setMany([$key => $value]);
    }

    public static function setMany(array $values): void
    {
        $values = static::withLegacyKeys($values);

        DB::transaction(function () use ($values) {
            foreach ($values as $key => $value) {
                static::query()->updateOrCreate(
                    ['key'   => $key],
                    ['value' => static::normalize($value)]
                );
            }
        });

        static::flushCache();
    }

    private static function withLegacyKeys(array $values): array
    {
        foreach ($values as $key => $value) {
            foreach (static::definition($key)['legacy'] ?? [] as $legacyKey) {
                $values[$legacyKey] = $value;
            }
        }

        return $values;
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function logoPath(): ?string
    {
        $path = static::get('company.logo');

        if (! $path) {
            return null;
        }

        $full = public_path('storage/' . ltrim($path, '/'));

        return is_file($full) ? $full : null;
    }

    public static function logoUrl(): ?string
    {
        return static::logoPath()
            ? asset('storage/' . ltrim(static::get('company.logo'), '/'))
            : null;
    }

    private static function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'int'     => (int) $value,
            'decimal' => (float) $value,
            'bool'    => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default   => (string) $value,
        };
    }

    private static function normalize(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            is_bool($value) => $value ? '1' : '0',
            default         => (string) $value,
        };
    }
}